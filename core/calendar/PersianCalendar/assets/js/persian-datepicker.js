/**
 * Persian Calendar - Date Picker
 * @package B2B_Procurement
 */
(function ($) {
    'use strict';

    var PC = {
        months: ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'],
        weekdays: ['ش','ی','د','س','چ','پ','ج'],
        activeDropdown: null,

        init: function () {
            $(document).on('click', '.b2b-pc-field, .b2b-pc-icon', function (e) {
                e.preventDefault();
                e.stopPropagation();
                var $input = $(this).closest('.b2b-pc-wrapper').find('.b2b-pc-field');
                if ($input.is(':disabled')) return;
                PC.openPicker($input);
            });

            $(document).on('click', function () {
                if (PC.activeDropdown) PC.closePicker();
            });
        },

        openPicker: function ($input) {
            if (PC.activeDropdown) PC.closePicker();

            var $wrapper = $input.closest('.b2b-pc-wrapper');
            var type = $wrapper.data('type') || 'date';
            var format = $wrapper.data('format') || 'Y/m/d';
            var minDate = $wrapper.data('min') || '';
            var maxDate = $wrapper.data('max') || '';

            var val = $input.val();
            var parsed = this.parseValue(val);
            var now = this.getNow();

            var year = parsed ? parsed.year : now.year;
            var month = parsed ? parsed.month : now.month;
            var day = parsed ? parsed.day : now.day;

            var $dropdown = $('<div class="b2b-pc-dropdown"></div>');
            $wrapper.append($dropdown);
            this.activeDropdown = $dropdown;

            this.renderCalendar($dropdown, $input, year, month, day, type, format, minDate, maxDate);
            $dropdown.addClass('show');
        },

        closePicker: function () {
            if (PC.activeDropdown) {
                PC.activeDropdown.removeClass('show');
                setTimeout(function () { PC.activeDropdown.remove(); }, 200);
                PC.activeDropdown = null;
            }
        },

        getNow: function () {
            var self = this;
            var result = { year: 1404, month: 1, day: 1, hour: 12, minute: 0 };
            $.ajax({
                url: b2bPC.ajaxUrl,
                type: 'POST',
                async: false,
                data: { action: 'b2b_pc_now', nonce: b2bPC.nonce },
                success: function (r) { if (r && r.success) result = r.data; }
            });
            return result;
        },

        parseValue: function (val) {
            if (!val) return null;
            val = val.replace(/[\/\-]/g, '/');
            var m = val.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})/);
            if (m) return { year: parseInt(m[1]), month: parseInt(m[2]), day: parseInt(m[3]) };
            return null;
        },

        renderCalendar: function ($dd, $input, year, month, day, type, format, minDate, maxDate) {
            var self = this;
            $dd.empty();

            // Header
            var $header = $('<div class="b2b-pc-header"></div>');
            $header.append('<button type="button" class="b2b-pc-header-btn pc-prev-month">&#9657;</button>');
            $header.append('<span class="b2b-pc-header-title pc-month-title">' + this.months[month - 1] + ' ' + this.toFA(year) + '</span>');
            $header.append('<button type="button" class="b2b-pc-header-btn pc-next-month">&#9667;</button>');
            $dd.append($header);

            // Month title click -> show month grid
            $header.find('.pc-month-title').on('click', function () {
                self.renderMonthGrid($dd, $input, year, type, format, minDate, maxDate);
            });

            // Weekdays
            var $weekdays = $('<div class="b2b-pc-weekdays"></div>');
            this.weekdays.forEach(function (w) { $weekdays.append('<div class="b2b-pc-weekday">' + w + '</div>'); });
            $dd.append($weekdays);

            // Grid
            var $grid = $('<div class="b2b-pc-grid"></div>');
            var firstDow = this.getJalaliFirstDayOfMonth(year, month);
            var daysInMonth = this.getDaysInMonth(year, month);
            var today = this.getNow();
            var nowStr = today.year + '/' + today.month + '/' + today.day;
            var selStr = this.parseValue($input.val());
            var selectedStr = selStr ? selStr.year + '/' + selStr.month + '/' + selStr.day : '';

            // Empty cells
            for (var i = 0; i < firstDow; i++) {
                $grid.append('<div class="b2b-pc-day empty"></div>');
            }
            // Day cells
            for (var d = 1; d <= daysInMonth; d++) {
                var ds = year + '/' + month + '/' + d;
                var cls = 'b2b-pc-day';
                var dow = (firstDow + d - 1) % 7;
                if (dow === 5 || dow === 6) cls += ' weekend';
                if (ds === nowStr) cls += ' today';
                if (ds === selectedStr) cls += ' selected';
                if (this.isDisabled(year, month, d, minDate, maxDate)) cls += ' disabled';
                $grid.append('<div class="' + cls + '" data-day="' + d + '">' + this.toFA(d) + '</div>');
            }
            $dd.append($grid);

            // Navigation
            $header.find('.pc-prev-month').on('click', function (e) {
                e.stopPropagation();
                var pm = month - 1;
                var py = year;
                if (pm < 1) { pm = 12; py--; }
                self.renderCalendar($dd, $input, py, pm, day, type, format, minDate, maxDate);
            });
            $header.find('.pc-next-month').on('click', function (e) {
                e.stopPropagation();
                var nm = month + 1;
                var ny = year;
                if (nm > 12) { nm = 1; ny++; }
                self.renderCalendar($dd, $input, ny, nm, day, type, format, minDate, maxDate);
            });

            // Day click
            $grid.on('click', '.b2b-pc-day:not(.empty):not(.disabled)', function (e) {
                e.stopPropagation();
                var d = parseInt($(this).data('day'));
                var val = year + '/' + month + '/' + d;
                if (type === 'datetime' || type === 'time') {
                    var now = self.getNow();
                    var h = String(parsed ? (parsed.hour || now.hour) : now.hour).padStart(2, '0');
                    var mi = String(parsed ? (parsed.minute || now.minute) : now.minute).padStart(2, '0');
                    val += ' ' + h + ':' + mi;
                }
                $input.val(self.toFA(val));
                self.closePicker();
                $input.trigger('change');
            });

            // Footer
            var $footer = $('<div class="b2b-pc-footer"></div>');
            $footer.append('<button type="button" class="b2b-pc-footer-btn today-btn">امروز</button>');
            $footer.append('<button type="button" class="b2b-pc-footer-btn clear-btn">پاک کردن</button>');
            $dd.append($footer);

            $footer.find('.today-btn').on('click', function (e) {
                e.stopPropagation();
                var t = self.getNow();
                var val = t.year + '/' + t.month + '/' + t.day;
                if (type === 'datetime' || type === 'time') {
                    val += ' ' + String(t.hour).padStart(2, '0') + ':' + String(t.minute).padStart(2, '0');
                }
                $input.val(self.toFA(val));
                self.closePicker();
                $input.trigger('change');
            });

            $footer.find('.clear-btn').on('click', function (e) {
                e.stopPropagation();
                $input.val('');
                self.closePicker();
                $input.trigger('change');
            });

            // Time picker
            if (type === 'datetime' || type === 'time') {
                var now = self.getNow();
                var $time = $('<div class="b2b-pc-time"></div>');
                var curVal = self.parseValue($input.val());
                var h = curVal && curVal.hour !== undefined ? curVal.hour : now.hour;
                var m = curVal && curVal.minute !== undefined ? curVal.minute : now.minute;
                $time.append('<input type="number" class="pc-hour" min="0" max="23" value="' + String(h).padStart(2, '0') + '" />');
                $time.append('<span class="b2b-pc-time-sep">:</span>');
                $time.append('<input type="number" class="pc-minute" min="0" max="59" value="' + String(m).padStart(2, '0') + '" />');
                $dd.append($time);

                $time.find('input').on('change', function () {
                    var h = $time.find('.pc-hour').val().padStart(2, '0');
                    var m = $time.find('.pc-minute').val().padStart(2, '0');
                    var dVal = $input.val().split(' ')[0] || '';
                    $input.val(self.toFA(dVal + ' ' + h + ':' + m));
                    $input.trigger('change');
                });
            }

            $dd.on('click', function (e) { e.stopPropagation(); });
        },

        renderMonthGrid: function ($dd, $input, year, type, format, minDate, maxDate) {
            var self = this;
            $dd.empty();

            var $header = $('<div class="b2b-pc-header"></div>');
            $header.append('<button type="button" class="b2b-pc-header-btn pc-prev-year">&#9657;</button>');
            $header.append('<span class="b2b-pc-header-title">' + this.toFA(year) + '</span>');
            $header.append('<button type="button" class="b2b-pc-header-btn pc-next-year">&#9667;</button>');
            $dd.append($header);

            var $grid = $('<div class="b2b-pc-month-grid"></div>');
            this.months.forEach(function (m, i) {
                $grid.append('<div class="b2b-pc-month-item" data-month="' + (i + 1) + '">' + m + '</div>');
            });
            $dd.append($grid);

            $header.find('.pc-prev-year').on('click', function (e) { e.stopPropagation(); self.renderMonthGrid($dd, $input, year - 1, type, format, minDate, maxDate); });
            $header.find('.pc-next-year').on('click', function (e) { e.stopPropagation(); self.renderMonthGrid($dd, $input, year + 1, type, format, minDate, maxDate); });

            $grid.on('click', '.b2b-pc-month-item', function (e) {
                e.stopPropagation();
                var m = parseInt($(this).data('month'));
                var now = self.getNow();
                var day = (year === now.year && m === now.month) ? now.day : 1;
                self.renderCalendar($dd, $input, year, m, day, type, format, minDate, maxDate);
            });
        },

        isDisabled: function (y, m, d, minDate, maxDate) {
            if (minDate) {
                var min = this.parseValue(minDate);
                if (min && this.cmp(y, m, d, min.year, min.month, min.day) < 0) return true;
            }
            if (maxDate) {
                var max = this.parseValue(maxDate);
                if (max && this.cmp(y, m, d, max.year, max.month, max.day) > 0) return true;
            }
            return false;
        },

        cmp: function (y1, m1, d1, y2, m2, d2) {
            if (y1 !== y2) return y1 < y2 ? -1 : 1;
            if (m1 !== m2) return m1 < m2 ? -1 : 1;
            if (d1 !== d2) return d1 < d2 ? -1 : 1;
            return 0;
        },

        getDaysInMonth: function (y, m) {
            return m <= 6 ? 31 : (m <= 11 ? 30 : (this.isLeapJalali(y) ? 30 : 29));
        },

        getJalaliFirstDayOfMonth: function (y, m) {
            var result = { f: 0 };
            $.ajax({
                url: b2bPC.ajaxUrl,
                type: 'POST',
                async: false,
                data: { action: 'b2b_pc_get_month', nonce: b2bPC.nonce, year: y, month: m },
                success: function (r) { if (r && r.success) result.f = r.data.first_day_of_week || 0; }
            });
            return result.f;
        },

        isLeapJalali: function (y) {
            var ref = 474, cycle = ((y - ref + 1948320) % 2820), years = cycle % 128;
            return years % 4 === 0;
        },

        toFA: function (num) {
            var p = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
            return String(num).replace(/\d/g, function (d) { return p[parseInt(d)]; });
        }
    };

    $(document).ready(function () { PC.init(); });
    window.B2BPC = window.B2BPC || {};
    $.extend(window.B2BPC, PC);
})(jQuery);
