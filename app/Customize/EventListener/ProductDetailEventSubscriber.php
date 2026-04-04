<?php

/*
 * 商品詳細ページにジオロケーション情報（都道府県）と送料見積もり機能を挿入するEventSubscriber。
 * テンプレートイベントを利用してコアファイルを変更せずに機能を追加する。
 */

namespace Customize\EventListener;

use Customize\Service\GeoLocationService;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\Master\PrefRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductDetailEventSubscriber implements EventSubscriberInterface
{
    /** @var GeoLocationService */
    private $geoLocationService;

    /** @var PrefRepository */
    private $prefRepository;

    /** @var RequestStack */
    private $requestStack;

    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(
        GeoLocationService $geoLocationService,
        PrefRepository $prefRepository,
        RequestStack $requestStack,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->geoLocationService = $geoLocationService;
        $this->prefRepository = $prefRepository;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Product/detail.twig' => 'onProductDetail',
        ];
    }

    /**
     * 商品詳細テンプレートイベントハンドラ。
     * IPアドレスからの都道府県推定結果と送料見積もり機能を挿入する。
     */
    public function onProductDetail(TemplateEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        // IPアドレスから都道府県を推定
        $clientIp = $request->getClientIp();
        $geoPref = $this->geoLocationService->getPrefFromIp($clientIp);

        // テンプレート変数を設定
        $event->setParameter('geo_pref', $geoPref);
        $event->setParameter('geo_prefs', $this->prefRepository->findAll());
        $event->setParameter(
            'shipping_estimate_url',
            $this->urlGenerator->generate('shipping_estimate', ['pref_id' => '__PREF_ID__'])
        );

        // テンプレートソースに挿入（「{# 商品コード #}」の直前に配置）
        $source = $event->getSource();
        $insertPoint = '{# 商品コード #}';
        $snippet = $this->getGeoLocationSnippet();

        $source = str_replace($insertPoint, $snippet . "\n                    " . $insertPoint, $source);
        $event->setSource($source);
    }

    /**
     * ジオロケーション表示・送料見積もりのTwigスニペットを返す。
     */
    private function getGeoLocationSnippet(): string
    {
        return <<<'TWIG'
{# ジオロケーション・送料見積もりセクション #}
                    <div class="ec-productRole__geolocation" style="margin: 16px 0; padding: 16px; background-color: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;">
                        <div id="geo-location-info" style="margin-bottom: 12px;">
                            {% if geo_pref is not null %}
                                <p style="margin: 0 0 8px 0; font-size: 14px; color: #333;">
                                    <span style="margin-right: 4px;">&#x1f4cd;</span>あなたは <strong>{{ geo_pref.name }}</strong> から閲覧しています
                                </p>
                            {% else %}
                                <p style="margin: 0 0 8px 0; font-size: 14px; color: #666;">
                                    お届け先の都道府県を選択すると送料の見積もりを表示します
                                </p>
                            {% endif %}
                        </div>
                        <div class="geo-shipping-estimate">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <label for="geo-pref-select" style="font-size: 14px; font-weight: bold; white-space: nowrap;">お届け先:</label>
                                <select id="geo-pref-select" class="form-select form-select-sm" style="max-width: 200px;">
                                    <option value="">都道府県を選択</option>
                                    {% for pref in geo_prefs %}
                                        <option value="{{ pref.id }}"{% if geo_pref is not null and geo_pref.id == pref.id %} selected{% endif %}>{{ pref.name }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                            <div id="shipping-estimate-result" style="margin-top: 12px; display: none;">
                                <p style="font-size: 13px; font-weight: bold; margin: 0 0 8px 0; color: #333;">送料見積もり:</p>
                                <div id="shipping-estimate-fees"></div>
                            </div>
                            <div id="shipping-estimate-loading" style="margin-top: 8px; display: none;">
                                <span style="font-size: 13px; color: #666;">送料を取得中...</span>
                            </div>
                            <div id="shipping-estimate-error" style="margin-top: 8px; display: none;">
                                <span style="font-size: 13px; color: #dc3545;">送料情報の取得に失敗しました</span>
                            </div>
                        </div>
                    </div>
                    <script>
                    (function() {
                        var select = document.getElementById('geo-pref-select');
                        var resultDiv = document.getElementById('shipping-estimate-result');
                        var feesDiv = document.getElementById('shipping-estimate-fees');
                        var loadingDiv = document.getElementById('shipping-estimate-loading');
                        var errorDiv = document.getElementById('shipping-estimate-error');
                        var urlTemplate = '{{ shipping_estimate_url|raw }}';

                        function fetchShippingEstimate(prefId) {
                            if (!prefId) {
                                resultDiv.style.display = 'none';
                                errorDiv.style.display = 'none';
                                return;
                            }
                            loadingDiv.style.display = 'block';
                            resultDiv.style.display = 'none';
                            errorDiv.style.display = 'none';

                            var url = urlTemplate.replace('__PREF_ID__', prefId);
                            fetch(url, {
                                headers: { 'Accept': 'application/json' }
                            })
                            .then(function(response) {
                                if (!response.ok) throw new Error('HTTP ' + response.status);
                                return response.json();
                            })
                            .then(function(data) {
                                loadingDiv.style.display = 'none';
                                if (data.fees && data.fees.length > 0) {
                                    var html = '<table style="width: 100%; font-size: 13px; border-collapse: collapse;">';
                                    html += '<thead><tr style="border-bottom: 1px solid #dee2e6;">';
                                    html += '<th style="text-align: left; padding: 4px 8px;">配送方法</th>';
                                    html += '<th style="text-align: right; padding: 4px 8px;">送料</th>';
                                    html += '</tr></thead><tbody>';
                                    data.fees.forEach(function(item) {
                                        html += '<tr style="border-bottom: 1px solid #f0f0f0;">';
                                        html += '<td style="padding: 4px 8px;">' + escapeHtml(item.delivery_name) + '</td>';
                                        html += '<td style="text-align: right; padding: 4px 8px; white-space: nowrap;">&yen; ' + Number(item.fee).toLocaleString() + '</td>';
                                        html += '</tr>';
                                    });
                                    html += '</tbody></table>';
                                    feesDiv.innerHTML = html;
                                    resultDiv.style.display = 'block';
                                } else {
                                    feesDiv.innerHTML = '<p style="font-size: 13px; color: #666; margin: 0;">この都道府県への送料情報はありません</p>';
                                    resultDiv.style.display = 'block';
                                }
                            })
                            .catch(function() {
                                loadingDiv.style.display = 'none';
                                errorDiv.style.display = 'block';
                            });
                        }

                        function escapeHtml(str) {
                            var div = document.createElement('div');
                            div.appendChild(document.createTextNode(str));
                            return div.innerHTML;
                        }

                        select.addEventListener('change', function() {
                            fetchShippingEstimate(this.value);
                        });

                        // 初期表示時に自動取得
                        if (select.value) {
                            fetchShippingEstimate(select.value);
                        }
                    })();
                    </script>
TWIG;
    }
}
