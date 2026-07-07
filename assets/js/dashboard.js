/**
 * B2B Dashboard & Reports
 * Version: 2.0.0
 */
(function ($) {
    'use strict';

    window.B2BDashboard = {
        init: function () {
            this.animateCharts();
        },

        animateCharts: function () {
            setTimeout(function () {
                $('.b2b-dash-chart-bar').each(function () {
                    var $el = $(this);
                    var w = $el.attr('data-width');
                    if (w) {
                        $el.css('width', w);
                    }
                });
            }, 200);
        }
    };

    $(document).ready(function () { B2BDashboard.init(); });
})(jQuery);
