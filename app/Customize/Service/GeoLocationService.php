<?php

/*
 * ユーザーのIPアドレスからジオロケーション（都道府県）を推定するサービス。
 * MaxMind GeoLite2-City データベースを使用。
 */

namespace Customize\Service;

use Eccube\Entity\Master\Pref;
use Eccube\Repository\Master\PrefRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeoLocationService
{
    /** @var PrefRepository */
    private $prefRepository;

    /** @var LoggerInterface */
    private $logger;

    /** @var string GeoLite2-City.mmdb のパス */
    private $geoDbPath;

    /** @var int|null 環境変数によるデフォルト都道府県ID */
    private $defaultPrefId;

    /**
     * GeoIP2の英語都道府県名からEC-CUBEの日本語都道府県名へのマッピング
     */
    private const PREF_NAME_MAP = [
        'Hokkaido' => '北海道',
        'Aomori' => '青森県',
        'Iwate' => '岩手県',
        'Miyagi' => '宮城県',
        'Akita' => '秋田県',
        'Yamagata' => '山形県',
        'Fukushima' => '福島県',
        'Ibaraki' => '茨城県',
        'Tochigi' => '栃木県',
        'Gunma' => '群馬県',
        'Saitama' => '埼玉県',
        'Chiba' => '千葉県',
        'Tokyo' => '東京都',
        'Kanagawa' => '神奈川県',
        'Niigata' => '新潟県',
        'Toyama' => '富山県',
        'Ishikawa' => '石川県',
        'Fukui' => '福井県',
        'Yamanashi' => '山梨県',
        'Nagano' => '長野県',
        'Gifu' => '岐阜県',
        'Shizuoka' => '静岡県',
        'Aichi' => '愛知県',
        'Mie' => '三重県',
        'Shiga' => '滋賀県',
        'Kyoto' => '京都府',
        'Osaka' => '大阪府',
        'Hyogo' => '兵庫県',
        'Nara' => '奈良県',
        'Wakayama' => '和歌山県',
        'Tottori' => '鳥取県',
        'Shimane' => '島根県',
        'Okayama' => '岡山県',
        'Hiroshima' => '広島県',
        'Yamaguchi' => '山口県',
        'Tokushima' => '徳島県',
        'Kagawa' => '香川県',
        'Ehime' => '愛媛県',
        'Kochi' => '高知県',
        'Fukuoka' => '福岡県',
        'Saga' => '佐賀県',
        'Nagasaki' => '長崎県',
        'Kumamoto' => '熊本県',
        'Oita' => '大分県',
        'Miyazaki' => '宮崎県',
        'Kagoshima' => '鹿児島県',
        'Okinawa' => '沖縄県',
    ];

    public function __construct(
        PrefRepository $prefRepository,
        LoggerInterface $logger,
        ParameterBagInterface $parameterBag
    ) {
        $this->prefRepository = $prefRepository;
        $this->logger = $logger;
        $projectDir = $parameterBag->get('kernel.project_dir');
        $this->geoDbPath = $projectDir . '/app/Customize/Resource/geodata/GeoLite2-City.mmdb';

        // 環境変数によるデフォルト都道府県ID（ローカル開発用）
        $envPrefId = $_ENV['ECCUBE_DEFAULT_PREF_ID'] ?? $_SERVER['ECCUBE_DEFAULT_PREF_ID'] ?? null;
        $this->defaultPrefId = $envPrefId !== null ? (int) $envPrefId : null;
    }

    /**
     * IPアドレスから都道府県エンティティを取得する。
     * GeoLite2 DBが存在しない場合やローカルIPの場合はデフォルト値またはnullを返す。
     *
     * @param string $ip クライアントIPアドレス
     *
     * @return Pref|null 推定された都道府県、判定不能の場合はnull
     */
    public function getPrefFromIp(string $ip): ?Pref
    {
        // プライベートIPやループバックの場合はGeoIPで判定不可
        if ($this->isLocalIp($ip)) {
            $this->logger->debug('GeoLocation: ローカルIPのためジオロケーション不可', ['ip' => $ip]);

            return $this->getDefaultPref();
        }

        // GeoLite2データベースの存在チェック
        if (!file_exists($this->geoDbPath)) {
            $this->logger->info('GeoLocation: GeoLite2-City.mmdb が見つかりません', [
                'path' => $this->geoDbPath,
            ]);

            return $this->getDefaultPref();
        }

        // GeoIP2ライブラリが利用可能かチェック
        if (!class_exists(\GeoIp2\Database\Reader::class)) {
            $this->logger->info('GeoLocation: GeoIP2ライブラリが未インストールです');

            return $this->getDefaultPref();
        }

        try {
            $reader = new \GeoIp2\Database\Reader($this->geoDbPath);
            $record = $reader->city($ip);

            // 日本以外の場合はnullを返す
            if ($record->country->isoCode !== 'JP') {
                $this->logger->debug('GeoLocation: 日本国外のアクセス', [
                    'ip' => $ip,
                    'country' => $record->country->isoCode,
                ]);

                return null;
            }

            // GeoIP2の最も詳細な行政区画名（都道府県）を取得
            $subdivisions = $record->subdivisions;
            if (empty($subdivisions)) {
                $this->logger->debug('GeoLocation: 都道府県情報なし', ['ip' => $ip]);

                return $this->getDefaultPref();
            }

            $subdivisionName = $subdivisions[0]->name; // 英語名（例: "Aichi"）

            if (!isset(self::PREF_NAME_MAP[$subdivisionName])) {
                $this->logger->warning('GeoLocation: マッピングされていない都道府県名', [
                    'subdivision' => $subdivisionName,
                ]);

                return $this->getDefaultPref();
            }

            $prefName = self::PREF_NAME_MAP[$subdivisionName];
            $pref = $this->prefRepository->findOneBy(['name' => $prefName]);

            if ($pref === null) {
                $this->logger->warning('GeoLocation: EC-CUBEの都道府県マスタに該当なし', [
                    'name' => $prefName,
                ]);
            }

            return $pref;
        } catch (\GeoIp2\Exception\AddressNotFoundException $e) {
            $this->logger->debug('GeoLocation: IPアドレスがデータベースに存在しません', [
                'ip' => $ip,
                'message' => $e->getMessage(),
            ]);

            return $this->getDefaultPref();
        } catch (\Exception $e) {
            $this->logger->error('GeoLocation: ジオロケーション処理中にエラー発生', [
                'ip' => $ip,
                'error' => $e->getMessage(),
            ]);

            return $this->getDefaultPref();
        }
    }

    /**
     * ローカル/プライベートIPアドレスかどうか判定する。
     */
    private function isLocalIp(string $ip): bool
    {
        // filter_var で FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE をチェック
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    /**
     * 環境変数で指定されたデフォルト都道府県を取得する。
     * 未設定の場合はnullを返す。
     */
    private function getDefaultPref(): ?Pref
    {
        if ($this->defaultPrefId === null) {
            return null;
        }

        return $this->prefRepository->find($this->defaultPrefId);
    }
}
