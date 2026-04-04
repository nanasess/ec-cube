/* カスタマイズ用Javascript */

/**
 * 商品検索サジェスト（オートコンプリート）機能
 *
 * ヘッダーおよびドロワー内の検索欄にキーワード入力すると、
 * 商品候補をドロップダウン表示する。
 */
$(function () {
    // サジェストAPIのURL（data属性から取得、なければデフォルト）
    var defaultSuggestUrl = '/products/suggest';

    // デバウンスタイマーとXHRオブジェクト
    var debounceTimer = null;
    var currentXhr = null;

    // 現在のキーボード選択インデックス (-1 = 未選択)
    var selectedIndex = -1;

    /**
     * サジェストドロップダウンを生成して検索欄の直下に挿入する
     */
    function getSuggestBox($input) {
        var $parent = $input.closest('.ec-headerSearch__keyword');
        var $suggest = $parent.find('.ec-suggest');
        if ($suggest.length === 0) {
            $suggest = $('<div class="ec-suggest"></div>');
            $parent.append($suggest);
        }
        return $suggest;
    }

    /**
     * サジェスト結果をドロップダウンに描画する
     */
    function renderSuggest($input, data) {
        var $suggest = getSuggestBox($input);
        $suggest.empty();
        selectedIndex = -1;

        if (data.length === 0) {
            $suggest.hide();
            return;
        }

        $.each(data, function (index, item) {
            var $item = $('<a class="ec-suggest__item" href=""></a>');
            $item.attr('href', item.url);

            // 商品画像
            var $img = $('<div class="ec-suggest__itemImage"></div>');
            $img.append($('<img>').attr('src', item.image).attr('alt', ''));
            $item.append($img);

            // 商品情報（名前・価格）
            var $info = $('<div class="ec-suggest__itemInfo"></div>');
            var $name = $('<div class="ec-suggest__itemName"></div>');
            // XSS対策: text()でエスケープ
            $name.text(item.name);
            $info.append($name);

            if (item.price !== null) {
                var $price = $('<div class="ec-suggest__itemPrice"></div>');
                $price.text('\u00A5' + item.price);
                $info.append($price);
            }

            $item.append($info);
            $suggest.append($item);
        });

        $suggest.show();
    }

    /**
     * サジェストを閉じる
     */
    function closeSuggest($input) {
        var $suggest = getSuggestBox($input);
        $suggest.hide();
        selectedIndex = -1;
    }

    /**
     * キーボードによる候補移動のハイライト更新
     */
    function updateHighlight($suggest) {
        var $items = $suggest.find('.ec-suggest__item');
        $items.removeClass('is-selected');
        if (selectedIndex >= 0 && selectedIndex < $items.length) {
            $items.eq(selectedIndex).addClass('is-selected');
        }
    }

    /**
     * 検索実行（APIリクエスト）
     */
    function doSearch($input) {
        var keyword = $input.val();
        if (!keyword || keyword.trim().length < 2) {
            closeSuggest($input);
            return;
        }

        // カテゴリIDを取得（同じフォーム内の .category_id セレクトボックス）
        var $form = $input.closest('form');
        var categoryId = $form.find('.category_id').val();

        var params = { keyword: keyword.trim() };
        if (categoryId) {
            params.category_id = categoryId;
        }

        // 前回のリクエストをキャンセル
        if (currentXhr) {
            currentXhr.abort();
        }

        var suggestUrl = $input.data('suggest-url') || defaultSuggestUrl;

        currentXhr = $.ajax({
            url: suggestUrl,
            type: 'GET',
            data: params,
            dataType: 'json',
            success: function (data) {
                renderSuggest($input, data);
            },
            error: function (xhr) {
                // abort以外のエラーの場合のみサジェストを閉じる
                if (xhr.statusText !== 'abort') {
                    closeSuggest($input);
                }
            },
            complete: function () {
                currentXhr = null;
            }
        });
    }

    /**
     * 入力イベント（デバウンス付き）
     */
    $(document).on('input', '.search-name', function () {
        var $input = $(this);
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            doSearch($input);
        }, 300);
    });

    /**
     * キーボードイベント（上下キー、Enter、Escape）
     */
    $(document).on('keydown', '.search-name', function (e) {
        var $input = $(this);
        var $suggest = getSuggestBox($input);
        var $items = $suggest.find('.ec-suggest__item');

        if (!$suggest.is(':visible') || $items.length === 0) {
            return;
        }

        switch (e.keyCode) {
            case 40: // 下キー
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, $items.length - 1);
                updateHighlight($suggest);
                break;
            case 38: // 上キー
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                updateHighlight($suggest);
                break;
            case 13: // Enterキー
                if (selectedIndex >= 0) {
                    e.preventDefault();
                    var href = $items.eq(selectedIndex).attr('href');
                    if (href) {
                        window.location.href = href;
                    }
                }
                // 未選択時はフォームの通常送信に任せる
                break;
            case 27: // Escapeキー
                closeSuggest($input);
                break;
        }
    });

    /**
     * フォーカスが外れたらサジェストを非表示にする
     * mousedown対策として遅延クローズ
     */
    $(document).on('blur', '.search-name', function () {
        var $input = $(this);
        setTimeout(function () {
            closeSuggest($input);
        }, 200);
    });

    /**
     * フォーカス時に入力値があれば再検索
     */
    $(document).on('focus', '.search-name', function () {
        var $input = $(this);
        if ($input.val() && $input.val().trim().length >= 2) {
            doSearch($input);
        }
    });
});
