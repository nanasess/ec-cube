<?php

/*
 * 発送日基準 売上集計サービス
 *
 * 1受注に複数出荷がある場合の重複排除に対応。
 * Shipping起点でOrderをJOINし、DISTINCT order_id で重複を排除する。
 */

namespace Customize\Service;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderStatus;

class SalesReportService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * 発送日基準の売上集計を取得する
     *
     * 2段階クエリで重複排除:
     *   1. Shipping から条件に合致する Order ID を DISTINCT で取得
     *   2. 取得した Order ID で受注を集計
     *
     * @param string $dateType 日付カラム（shipping_date / shipping_delivery_date）
     * @param \DateTimeInterface $dateStart 集計開始日
     * @param \DateTimeInterface $dateEnd 集計終了日
     * @param string $unit 集計単位（daily / monthly / yearly）
     * @param array $orderStatusIds 受注ステータスID配列
     *
     * @return array 集計結果配列
     */
    public function collect(
        string $dateType,
        \DateTimeInterface $dateStart,
        \DateTimeInterface $dateEnd,
        string $unit,
        array $orderStatusIds = []
    ): array {
        $conn = $this->entityManager->getConnection();

        // 日付カラムの決定（shipping_date または delivery_date）
        $dateColumn = $dateType === 'shipping_delivery_date' ? 'delivery_date' : 'shipping_date';

        // 集計終了日は当日を含むため翌日にする
        $dateEndInclusive = \DateTime::createFromInterface($dateEnd)->modify('+1 day');

        // ステップ1: 条件に合致するShippingから重複排除したOrder IDと発送日を取得
        // 1受注に複数出荷がある場合、最初の発送日（MIN）を採用する
        $sql = 'SELECT s.order_id, MIN(s.'.$dateColumn.') AS report_date'
            .' FROM dtb_shipping s'
            .' INNER JOIN dtb_order o ON s.order_id = o.id'
            .' WHERE s.'.$dateColumn.' IS NOT NULL'
            .' AND s.'.$dateColumn.' >= :date_start'
            .' AND s.'.$dateColumn.' < :date_end';

        $params = [
            'date_start' => $dateStart->format('Y-m-d 00:00:00'),
            'date_end' => $dateEndInclusive->format('Y-m-d 00:00:00'),
        ];

        // 受注ステータスフィルタ
        if (!empty($orderStatusIds)) {
            $placeholders = [];
            foreach ($orderStatusIds as $i => $statusId) {
                $key = 'status_'.$i;
                $placeholders[] = ':'.$key;
                $params[$key] = $statusId;
            }
            $sql .= ' AND o.order_status_id IN ('.implode(', ', $placeholders).')';
        } else {
            // デフォルト: キャンセル・返品・決済処理中・購入処理中を除外
            $sql .= ' AND o.order_status_id NOT IN (:ex_cancel, :ex_returned, :ex_pending, :ex_processing)';
            $params['ex_cancel'] = OrderStatus::CANCEL;
            $params['ex_returned'] = OrderStatus::RETURNED;
            $params['ex_pending'] = OrderStatus::PENDING;
            $params['ex_processing'] = OrderStatus::PROCESSING;
        }

        $sql .= ' GROUP BY s.order_id';

        // ステップ2: サブクエリの結果から期間ごとに集計する
        $isPostgreSQL = $conn->getDatabasePlatform() instanceof PostgreSQLPlatform;
        $dateFormat = $this->getDateFormatForPlatform($unit, $isPostgreSQL);

        $outerSql = 'SELECT '.$dateFormat.' AS report_period,'
            .' COUNT(*) AS order_count,'
            .' SUM(o.payment_total) AS sales_total'
            .' FROM dtb_order o'
            .' INNER JOIN ('.$sql.') sub ON o.id = sub.order_id'
            .' GROUP BY report_period'
            .' ORDER BY report_period ASC';

        $stmt = $conn->executeQuery($outerSql, $params);
        $results = $stmt->fetchAllAssociative();

        // 期間内の全日付/月/年を埋める（データなしの期間は0件で表示）
        return $this->fillMissingPeriods($results, $dateStart, $dateEnd, $unit);
    }

    /**
     * DB プラットフォームに応じた日付フォーマットSQL断片を返す
     */
    private function getDateFormatForPlatform(string $unit, bool $isPostgreSQL): string
    {
        // sub.report_date を基準にフォーマット
        if ($isPostgreSQL) {
            return match ($unit) {
                'monthly' => "TO_CHAR(sub.report_date, 'YYYY-MM')",
                'yearly' => "TO_CHAR(sub.report_date, 'YYYY')",
                default => "TO_CHAR(sub.report_date, 'YYYY-MM-DD')",
            };
        }

        // MySQL / MariaDB
        return match ($unit) {
            'monthly' => "DATE_FORMAT(sub.report_date, '%Y-%m')",
            'yearly' => "DATE_FORMAT(sub.report_date, '%Y')",
            default => "DATE_FORMAT(sub.report_date, '%Y-%m-%d')",
        };
    }

    /**
     * 集計結果にデータなし期間を0件で埋める
     */
    private function fillMissingPeriods(array $results, \DateTimeInterface $start, \DateTimeInterface $end, string $unit): array
    {
        // 既存データをキー引きにする
        $indexed = [];
        foreach ($results as $row) {
            $indexed[$row['report_period']] = $row;
        }

        $filled = [];
        $current = new \DateTime($start->format('Y-m-d'));
        $endDate = new \DateTime($end->format('Y-m-d'));

        while ($current <= $endDate) {
            $key = match ($unit) {
                'monthly' => $current->format('Y-m'),
                'yearly' => $current->format('Y'),
                default => $current->format('Y-m-d'),
            };

            if (!isset($filled[$key])) {
                $filled[$key] = $indexed[$key] ?? [
                    'report_period' => $key,
                    'order_count' => 0,
                    'sales_total' => 0,
                ];
            }

            // 次の期間に進める
            match ($unit) {
                'monthly' => $current->modify('+1 month'),
                'yearly' => $current->modify('+1 year'),
                default => $current->modify('+1 day'),
            };
        }

        return array_values($filled);
    }
}
