<?php

/*
 * 発送日基準 売上集計コントローラ
 *
 * 管理画面「受注管理 > 売上集計」メニューを追加し、
 * 発送日基準の売上集計・CSV出力機能を提供する。
 */

namespace Customize\Controller\Admin;

use Customize\Form\Type\Admin\SalesReportType;
use Customize\Service\SalesReportService;
use Eccube\Common\EccubeNav;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class SalesReportController extends AbstractController implements EccubeNav
{
    private SalesReportService $salesReportService;

    public function __construct(SalesReportService $salesReportService)
    {
        $this->salesReportService = $salesReportService;
    }

    /**
     * ナビゲーション: 受注管理 > 売上集計
     */
    public static function getNav(): array
    {
        return [
            'order' => [
                'children' => [
                    'sales_report' => [
                        'name' => '売上集計',
                        'url' => 'admin_sales_report',
                    ],
                ],
            ],
        ];
    }

    /**
     * 売上集計画面
     *
     * @Route("/%eccube_admin_route%/sales_report", name="admin_sales_report", methods={"GET", "POST"})
     */
    public function index(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->createForm(SalesReportType::class);
        $form->handleRequest($request);

        $results = [];
        $totalOrderCount = 0;
        $totalSalesAmount = 0;

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // 受注ステータスIDの配列を作成
            $orderStatusIds = [];
            if (!empty($data['order_statuses'])) {
                foreach ($data['order_statuses'] as $status) {
                    $orderStatusIds[] = $status->getId();
                }
            }

            $results = $this->salesReportService->collect(
                $data['date_type'],
                $data['date_start'],
                $data['date_end'],
                $data['unit'],
                $orderStatusIds
            );

            // 合計計算
            foreach ($results as $row) {
                $totalOrderCount += (int) $row['order_count'];
                $totalSalesAmount += (int) $row['sales_total'];
            }
        }

        return $this->render('@admin/SalesReport/index.twig', [
            'form' => $form->createView(),
            'results' => $results,
            'totalOrderCount' => $totalOrderCount,
            'totalSalesAmount' => $totalSalesAmount,
        ]);
    }

    /**
     * CSV出力
     *
     * @Route("/%eccube_admin_route%/sales_report/csv", name="admin_sales_report_csv", methods={"POST"})
     */
    public function csv(Request $request): StreamedResponse
    {
        $form = $this->createForm(SalesReportType::class);
        $form->handleRequest($request);

        $results = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $orderStatusIds = [];
            if (!empty($data['order_statuses'])) {
                foreach ($data['order_statuses'] as $status) {
                    $orderStatusIds[] = $status->getId();
                }
            }

            $results = $this->salesReportService->collect(
                $data['date_type'],
                $data['date_start'],
                $data['date_end'],
                $data['unit'],
                $orderStatusIds
            );
        }

        $filename = 'sales_report_'.(new \DateTime())->format('YmdHis').'.csv';

        $response = new StreamedResponse(function () use ($results) {
            $handle = fopen('php://output', 'w');

            // BOM付きSJIS出力（Excel対応）
            // まずUTF-8で書き込み、後でSJISに変換
            $header = ['日付', '受注件数', '売上合計'];
            fputcsv($handle, array_map(fn ($v) => mb_convert_encoding($v, 'SJIS-win', 'UTF-8'), $header));

            foreach ($results as $row) {
                $line = [
                    $row['report_period'],
                    $row['order_count'],
                    $row['sales_total'],
                ];
                fputcsv($handle, array_map(fn ($v) => mb_convert_encoding((string) $v, 'SJIS-win', 'UTF-8'), $line));
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
