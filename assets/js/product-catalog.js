/**
 * B2B Product Catalog - Version 2.1.0
 */
(function ($) {
    'use strict';

    window.B2BCatalog = {
        productPage: 1,
        categoryPage: 1,
        productView: 'list',

        init: function () {
            if ($('#product-search').length) {
                this.initProductSearch();
                this.initViewToggle();
            }
            if ($('#category-search').length) {
                this.initCategorySearch();
            }
            if ($('#product-form').length || $('#category-form').length) {
                this.initFormSubmit();
            }
            if ($('.b2b-media-upload-btn').length) {
                this.initMediaUploader();
            }
        },

        initViewToggle: function () {
            var self = this;
            $('[data-view]').on('click', function () {
                var view = $(this).data('view');
                self.productView = view;
                $('[data-view]').removeClass('b2b-active-view');
                $(this).addClass('b2b-active-view');
                self.loadProducts();
            });
            $('[data-view="list"]').addClass('b2b-active-view');
        },

        // ==================== PRODUCTS ====================
        initProductSearch: function () {
            var self = this;
            $('#product-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.productPage = 1; self.loadProducts(); }, 400);
            });
            $('#product-status').on('change', function () {
                self.productPage = 1;
                self.loadProducts();
            });
            self.loadProducts();
        },

        loadProducts: function () {
            var self = this;
            var $c = $('#product-table-container');
            if (!$c.length) return;

            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div><p style="color:#6B7280;margin-top:16px;">در حال بارگذاری...</p></div>');

            $.post(b2bProcurement.ajaxUrl, {
                action: 'b2b_catalog_get_products',
                _b2b_nonce: b2bProcurement.nonce,
                search: $('#product-search').val() || '',
                status: $('#product-status').val() || '',
                category_id: $('#product-category').val() || '',
                page: self.productPage,
                per_page: 20
            }, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    if (self.productView === 'grid') {
                        self.renderProductGrid(r.data);
                    } else {
                        self.renderProductTable(r.data);
                    }
                    self.renderProductPag(r.data);
                    $('#product-count').text(toPersianNum(r.data.total) + ' مورد');
                } else {
                    var msg = (r && r.data && r.data.message) ? r.data.message : 'خطا در بارگذاری';
                    $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">' + msg + '</p></div></div></div>');
                }
            }).fail(function (xhr, status, error) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">خطا: ' + error + '</p></div></div></div>');
            });
        },

        renderProductTable: function (d) {
            var self = this;
            var $c = $('#product-table-container');
            if (!d.items.length) {
                $c.html(self.emptyState('محصولی یافت نشد', 'افزودن محصول', 'admin.php?page=b2b-product-add'));
                return;
            }
            var h = '<div class="b2b-card"><div class="b2b-card-body" style="padding:0;overflow-x:auto;"><table class="b2b-table"><thead><tr>';
            h += '<th class="b2b-col-check"><input type="checkbox" class="b2b-check-all" /></th>';
            h += '<th>تصویر</th><th>کد</th><th>نام</th><th>قیمت</th><th>واحد</th><th>وضعیت</th><th>عملیات</th>';
            h += '</tr></thead><tbody>';
            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var sc = x.status == 1 ? 'b2b-status-active' : 'b2b-status-inactive';
                var sl = x.status == 1 ? 'فعال' : 'غیرفعال';
                var img = (x.images && x.images.length > 0) ? x.images[0] : '';
                var price = x.regular_price ? toPersianNum(x.regular_price) + ' تومان' : '-';
                h += '<tr>';
                h += '<td class="b2b-col-check"><input type="checkbox" class="b2b-row-check" value="' + x.id + '" /></td>';
                h += '<td>' + (img ? '<img src="' + esc(img) + '" style="width:40px;height:40px;object-fit:cover;border-radius:6px;" />' : '<div style="width:40px;height:40px;background:#f3f4f6;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#9ca3af;font-size:20px;">&#128247;</div>') + '</td>';
                h += '<td><span class="b2b-badge b2b-badge-primary">' + esc(x.sku) + '</span></td>';
                h += '<td><strong>' + esc(x.name_fa) + '</strong></td>';
                h += '<td>' + price + '</td>';
                h += '<td>' + esc(x.base_unit_name || x.base_unit) + '</td>';
                h += '<td><span class="b2b-status ' + sc + '">' + sl + '</span></td>';
                h += '<td style="white-space:nowrap;">';
                h += '<a href="' + self.adminUrl('admin.php?page=b2b-product-edit&id=' + x.id) + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost" title="ویرایش">&#9998;</a> ';
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost" title="حذف" onclick="B2BCatalog.deleteProduct(' + x.id + ')">&#128465;</button>';
                h += '</td></tr>';
            }
            h += '</tbody></table></div></div>';
            $c.html(h);
        },

        renderProductGrid: function (d) {
            var self = this;
            var $c = $('#product-table-container');
            var h = '<div class="b2b-product-grid">';
            for (var i = 0; i < d.items.length; i++) {
                var x = d.items[i];
                var sc = x.status == 1 ? 'b2b-status-active' : 'b2b-status-inactive';
                var sl = x.status == 1 ? 'فعال' : 'غیرفعال';
                var img = (x.images && x.images.length > 0) ? x.images[0] : '';
                var price = x.regular_price ? toPersianNum(x.regular_price) + ' تومان' : '-';
                h += '<div class="b2b-product-card">';
                h += '<div class="b2b-product-card-img">' + (img ? '<img src="' + esc(img) + '" />' : '<div class="b2b-product-card-placeholder">&#128247;</div>') + '</div>';
                h += '<div class="b2b-product-card-body">';
                h += '<div class="b2b-product-card-sku"><span class="b2b-badge b2b-badge-primary">' + esc(x.sku) + '</span></div>';
                h += '<div class="b2b-product-card-name">' + esc(x.name_fa) + '</div>';
                h += '<div class="b2b-product-card-price">' + price + '</div>';
                h += '<div class="b2b-product-card-meta"><span>' + esc(x.base_unit_name || x.base_unit) + '</span><span class="b2b-status ' + sc + '">' + sl + '</span></div>';
                h += '<div class="b2b-product-card-actions">';
                h += '<a href="' + self.adminUrl('admin.php?page=b2b-product-edit&id=' + x.id) + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost">ویرایش</a> ';
                h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost" onclick="B2BCatalog.deleteProduct(' + x.id + ')">حذف</button>';
                h += '</div></div></div>';
            }
            h += '</div>';
            $c.html(h);
        },

        renderProductPag: function (d) {
            var self = this;
            var $p = $('#product-pagination');
            if (!$p.length || d.pages <= 1) { if ($p.length) $p.html(''); return; }
            var h = '<div class="b2b-table-pagination"><div class="b2b-pagination-info">صفحه ' + toPersianNum(d.page) + ' از ' + toPersianNum(d.pages) + '</div><div class="b2b-pagination-links">';
            if (d.page > 1) h += '<button class="b2b-page-link" onclick="B2BCatalog.goProduct(' + (d.page - 1) + ')">&laquo;</button>';
            for (var i = 1; i <= d.pages; i++) {
                if (i === d.page) h += '<span class="b2b-page-link b2b-page-active">' + toPersianNum(i) + '</span>';
                else if (i === 1 || i === d.pages || Math.abs(i - d.page) <= 2) h += '<button class="b2b-page-link" onclick="B2BCatalog.goProduct(' + i + ')">' + toPersianNum(i) + '</button>';
            }
            if (d.page < d.pages) h += '<button class="b2b-page-link" onclick="B2BCatalog.goProduct(' + (d.page + 1) + ')">&raquo;</button>';
            h += '</div></div>';
            $p.html(h);
        },

        goProduct: function (p) { this.productPage = p; this.loadProducts(); },

        deleteProduct: function (id) {
            if (!confirm('آیا از حذف این محصول اطمینان دارید؟')) return;
            var self = this;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_catalog_delete_product', _b2b_nonce: b2bProcurement.nonce, product_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.loadProducts(); }
                else { B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error'); }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        quickEdit: function (id) {
            var self = this;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_catalog_get_products', _b2b_nonce: b2bProcurement.nonce, per_page: 1000 }, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    var product = null;
                    for (var i = 0; i < r.data.items.length; i++) {
                        if (r.data.items[i].id == id) { product = r.data.items[i]; break; }
                    }
                    if (product) {
                        var $m = $('#quick-edit-modal');
                        $m.find('[name="product_id"]').val(product.id);
                        $m.find('[name="name_fa"]').val(product.name_fa);
                        $m.find('[name="sku"]').val(product.sku);
                        $m.find('[name="base_unit"]').val(product.base_unit);
                        $m.find('[name="min_order_qty"]').val(product.min_order_qty);
                        $m.find('[name="lead_time_days"]').val(product.lead_time_days);
                        $m.find('[name="status"]').val(product.status);
                        self.openModal('quick-edit-modal');
                    } else {
                        B2BAdmin.toast('محصول یافت نشد', 'error');
                    }
                }
            }).fail(function () { B2BAdmin.toast('خطا در بارگذاری اطلاعات', 'error'); });
        },

        saveQuickEdit: function () {
            var self = this;
            var $form = $('#quick-edit-form');
            $.post(b2bProcurement.ajaxUrl, $form.serialize() + '&action=b2b_product_save&_b2b_nonce=' + b2bProcurement.nonce, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message || 'ذخیره شد', 'success'); self.closeModal('quick-edit-modal'); self.loadProducts(); }
                else { B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error'); }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== CATEGORIES ====================
        initCategorySearch: function () {
            var self = this;
            $('#category-search').on('keyup', function () {
                clearTimeout(self._timer);
                self._timer = setTimeout(function () { self.categoryPage = 1; self.loadCategories(); }, 400);
            });
            self.loadCategories();
        },

        loadCategories: function () {
            var self = this;
            var $c = $('#category-table-container');
            if (!$c.length) return;

            $c.html('<div style="text-align:center;padding:40px;"><div class="b2b-spinner-lg" style="margin:0 auto;"></div></div>');

            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_catalog_get_categories', _b2b_nonce: b2bProcurement.nonce, search: $('#category-search').val() || '', page: self.categoryPage, per_page: 50 }, function (r) {
                if (r && r.success && r.data && r.data.items) {
                    self.renderCategoryTree(r.data.items);
                    $('#category-count').text(toPersianNum(r.data.total) + ' مورد');
                } else {
                    $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">خطا در بارگذاری</p></div></div></div>');
                }
            }).fail(function (xhr, s, e) {
                $c.html('<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><p class="b2b-empty-state-text">خطا: ' + e + '</p></div></div></div>');
            });
        },

        renderCategoryTree: function (items) {
            var self = this;
            var $c = $('#category-table-container');
            if (!items.length) { $c.html(self.emptyState('دسته‌بندی یافت نشد', 'افزودن دسته‌بندی', 'admin.php?page=b2b-category-add')); return; }
            var map = {}, roots = [];
            for (var i = 0; i < items.length; i++) { items[i].children = []; map[items[i].id] = items[i]; }
            for (var j = 0; j < items.length; j++) { if (items[j].parent && map[items[j].parent]) map[items[j].parent].children.push(items[j]); else roots.push(items[j]); }
            var h = '<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-category-tree">';
            for (var k = 0; k < roots.length; k++) h += self.renderCategoryNode(roots[k], 0);
            h += '</div><div style="margin-top:16px;"><a href="' + self.adminUrl('admin.php?page=b2b-category-add') + '" class="b2b-btn b2b-btn-primary">&#10010; افزودن دسته‌بندی</a></div></div></div>';
            $c.html(h);
        },

        renderCategoryNode: function (node, level) {
            var self = this;
            var indent = level * 24;
            var hc = node.children && node.children.length > 0;
            var h = '<div class="b2b-cat-node" data-id="' + node.id + '" draggable="true" style="padding-right:' + indent + 'px;">';
            h += '<div class="b2b-cat-row">';
            h += '<span class="b2b-cat-drag">&#9776;</span>';
            h += '<span class="b2b-cat-toggle' + (hc ? '' : ' b2b-cat-toggle-empty') + '" onclick="B2BCatalog.toggleChildren(this)">&#9660;</span>';
            h += '<span class="b2b-cat-icon">&#128193;</span>';
            h += '<span class="b2b-cat-name"><strong>' + esc(node.name) + '</strong></span>';
            h += '<span class="b2b-cat-slug"><span class="b2b-badge b2b-badge-primary">' + esc(node.slug) + '</span></span>';
            h += '<span class="b2b-cat-count">' + toPersianNum(node.count || 0) + ' محصول</span>';
            h += '<span class="b2b-cat-actions">';
            h += '<a href="' + self.adminUrl('admin.php?page=b2b-category-edit&id=' + node.id) + '" class="b2b-btn b2b-btn-sm b2b-btn-ghost" title="ویرایش">&#9998;</a> ';
            h += '<button type="button" class="b2b-btn b2b-btn-sm b2b-btn-ghost" title="حذف" onclick="B2BCatalog.deleteCategory(' + node.id + ')">&#128465;</button>';
            h += '</span></div>';
            if (hc) { h += '<div class="b2b-cat-children">'; for (var i = 0; i < node.children.length; i++) h += self.renderCategoryNode(node.children[i], level + 1); h += '</div>'; }
            h += '</div>';
            return h;
        },

        toggleChildren: function (el) { var $n = $(el).closest('.b2b-cat-node'); var $c = $n.children('.b2b-cat-children'); if ($c.length) { $c.toggle(); $(el).toggleClass('b2b-cat-toggle-open'); } },

        deleteCategory: function (id) {
            if (!confirm('آیا از حذف این دسته‌بندی اطمینان دارید؟')) return;
            var self = this;
            $.post(b2bProcurement.ajaxUrl, { action: 'b2b_catalog_delete_category', _b2b_nonce: b2bProcurement.nonce, category_id: id }, function (r) {
                if (r && r.success) { B2BAdmin.toast(r.data.message, 'success'); self.loadCategories(); }
                else { B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error'); }
            }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); });
        },

        // ==================== FORM SUBMIT ====================
        initFormSubmit: function () {
            var self = this;
            $('#product-form, #category-form').on('submit', function (e) {
                e.preventDefault();
                var $form = $(this);
                var $btn = $form.find('button[type="submit"]');
                var action = $form.find('[name="action"]').val();
                $btn.prop('disabled', true).html('<span class="b2b-spinner"></span> در حال ذخیره...');
                $.post(b2bProcurement.ajaxUrl, $form.serialize(), function (r) {
                    if (r && r.success) {
                        B2BAdmin.toast(r.data.message || 'ذخیره شد', 'success');
                        setTimeout(function () { window.location.href = action === 'b2b_product_save' ? '/wp-admin/admin.php?page=b2b-products' : '/wp-admin/admin.php?page=b2b-categories'; }, 1000);
                    } else {
                        B2BAdmin.toast((r && r.data && r.data.message) ? r.data.message : 'خطا', 'error');
                        $btn.prop('disabled', false).html('ذخیره');
                    }
                }).fail(function () { B2BAdmin.toast('خطا در ارتباط با سرور', 'error'); $btn.prop('disabled', false).html('ذخیره'); });
            });
        },

        // ==================== MEDIA ====================
        initMediaUploader: function () {
            var self = this;

            // Open media library on button click
            $(document).on('click', '.b2b-media-upload-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var isMultiple = $btn.data('multiple') === true || $btn.data('multiple') === 'true';
                var targetId = $btn.data('target');
                var previewId = $btn.data('preview');
                var $wrap = $btn.closest('.b2b-media-upload, .b2b-media-wrap');
                var $input = targetId ? $('#' + targetId) : $wrap.find('input[type="hidden"]');
                var $preview = previewId ? $('#' + previewId) : $wrap.find('.b2b-media-preview');

                if (typeof wp === 'undefined' || typeof wp.media === 'undefined') { alert('کتابخانه رسانه بارگذاری نشده.'); return; }

                var frame = wp.media({
                    title: isMultiple ? 'انتخاب تصاویر' : 'انتخاب تصویر',
                    button: { text: 'انتخاب' },
                    multiple: isMultiple,
                    library: { type: 'image' }
                });

                frame.on('select', function () {
                    if (isMultiple) {
                        // Append new images to existing
                        var selection = frame.state().get('selection');
                        var existingIds = $input.val() ? $input.val().split(',').filter(Boolean) : [];
                        selection.each(function (att) {
                            var a = att.toJSON();
                            if (existingIds.indexOf(String(a.id)) === -1) {
                                existingIds.push(String(a.id));
                            }
                        });
                        $input.val(existingIds.join(','));
                        self.refreshGalleryPreview($input, $preview);
                    } else {
                        var a = frame.state().get('selection').first().toJSON();
                        $input.val(a.id);
                        var thumb = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
                        $preview.html('<img src="' + thumb + '" style="max-width:200px;border-radius:8px;" />');
                    }
                });
                frame.open();
            });

            // Remove single image
            $(document).on('click', '.b2b-media-remove-btn', function (e) {
                e.preventDefault();
                var $wrap = $(this).closest('.b2b-media-upload, .b2b-media-wrap');
                $wrap.find('.b2b-media-preview').html('');
                $wrap.find('input[type="hidden"]').val('');
            });

            // Remove single gallery image
            $(document).on('click', '.b2b-gallery-remove', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $img = $(this).closest('.b2b-gallery-thumb');
                var imgId = $img.data('id');
                var $input = $('#gallery_ids');
                var ids = $input.val() ? $input.val().split(',').filter(Boolean) : [];
                ids = ids.filter(function (id) { return String(id) !== String(imgId); });
                $input.val(ids.join(','));
                $img.fadeOut(200, function () { $(this).remove(); });
            });

            // Drag & Drop on media upload areas
            self.initDragDrop();

            // Load existing gallery previews from server
            if ($('#gallery_ids').length) {
                var galleryVal = $('#gallery_ids').val();
                if (galleryVal && galleryVal.trim()) {
                    self.refreshGalleryPreview($('#gallery_ids'), $('#gallery-preview'));
                }
            }
        },

        refreshGalleryPreview: function ($input, $preview) {
            var ids = $input.val() ? $input.val().split(',').filter(Boolean) : [];
            if (!ids.length) { $preview.html(''); return; }

            var html = '';
            for (var i = 0; i < ids.length; i++) {
                html += '<div class="b2b-gallery-thumb" data-id="' + ids[i] + '" style="position:relative;display:inline-block;">' +
                    '<img src="' + '/wp-admin/admin-ajax.php?action=b2b_get_thumb&id=' + ids[i] + '" style="width:80px;height:80px;object-fit:cover;border-radius:6px;border:2px solid #ECE6F8;" onerror="this.src=\'data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22><rect fill=%22%23f3f4f6%22 width=%2280%22 height=%2280%22/><text x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%239ca3af%22 font-size=%2214%22>&#128247;</text></svg>\'" />' +
                    '<button type="button" class="b2b-gallery-remove" style="position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#EF4444;color:#fff;border:none;font-size:12px;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;" title="حذف">&#10005;</button>' +
                    '</div>';
            }
            $preview.html(html);
        },

        initDragDrop: function () {
            var self = this;

            // Make media upload zones drag-drop targets
            $(document).find('.b2b-media-upload').each(function () {
                var $zone = $(this);
                if ($zone.data('dragdrop-init')) return;
                $zone.data('dragdrop-init', true);

                // Prevent default drag behaviors
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function (eventName) {
                    $zone[0].addEventListener(eventName, function (e) { e.preventDefault(); e.stopPropagation(); }, false);
                });

                // Highlight on drag over
                ['dragenter', 'dragover'].forEach(function (eventName) {
                    $zone[0].addEventListener(eventName, function () { $zone.addClass('b2b-drag-over'); }, false);
                });

                // Remove highlight on drag leave / drop
                ['dragleave', 'drop'].forEach(function (eventName) {
                    $zone[0].addEventListener(eventName, function () { $zone.removeClass('b2b-drag-over'); }, false);
                });

                // Handle drop
                $zone[0].addEventListener('drop', function (e) {
                    var files = e.dataTransfer.files;
                    if (!files || !files.length) return;

                    var isMultiple = $zone.find('.b2b-media-upload-btn').data('multiple') === true || $zone.find('.b2b-media-upload-btn').data('multiple') === 'true';
                    var $input = $zone.find('input[type="hidden"]');
                    var $preview = $zone.find('.b2b-media-preview');

                    for (var i = 0; i < files.length; i++) {
                        var file = files[i];
                        if (!file.type.startsWith('image/')) continue;

                        var formData = new FormData();
                        formData.append('file', file);
                        formData.append('action', 'b2b_pr_upload');
                        formData.append('nonce', typeof b2bPR !== 'undefined' ? b2bPR.nonce : (typeof b2bProcurement !== 'undefined' ? b2bProcurement.nonce : ''));

                        $.ajax({
                            url: (typeof b2bProcurement !== 'undefined') ? b2bProcurement.ajaxUrl : '/wp-admin/admin-ajax.php',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (r) {
                                if (r && r.success) {
                                    if (isMultiple) {
                                        var ids = $input.val() ? $input.val().split(',').filter(Boolean) : [];
                                        ids.push(String(r.data.id));
                                        $input.val(ids.join(','));
                                        self.refreshGalleryPreview($input, $preview);
                                    } else {
                                        $input.val(r.data.id);
                                        $preview.html('<img src="' + r.data.url + '" style="max-width:200px;border-radius:8px;" />');
                                    }
                                }
                            }
                        });
                    }
                }, false);
            });
        },

        // ==================== MODAL ====================
        openModal: function (id) { $('#' + id).addClass('is-active'); $('body').addClass('b2b-modal-open'); },
        closeModal: function (id) { $('#' + id).removeClass('is-active'); $('body').removeClass('b2b-modal-open'); },

        adminUrl: function (p) { return window.location.origin + '/wp-admin/' + p; },
        emptyState: function (m, b, u) { return '<div class="b2b-card"><div class="b2b-card-body"><div class="b2b-empty-state"><div class="b2b-empty-state-icon">&#128230;</div><p class="b2b-empty-state-text">' + m + '</p><div class="b2b-empty-state-action"><a href="' + this.adminUrl(u) + '" class="b2b-btn b2b-btn-primary">' + b + '</a></div></div></div></div>'; }
    };

    function esc(t) { if (!t) return ''; var d = document.createElement('div'); d.appendChild(document.createTextNode(t)); return d.innerHTML; }
    $(document).ready(function () { B2BCatalog.init(); });
})(jQuery);
