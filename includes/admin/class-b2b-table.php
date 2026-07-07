<?php
/**
 * Table - Reusable table component.
 *
 * @package B2B_Procurement
 */

defined('ABSPATH') || exit;

/**
 * Class B2B_Procurement_Table
 *
 * Provides a reusable table with pagination, search, and sorting.
 *
 * @since 1.0.0
 */
class B2B_Procurement_Table {

    /**
     * Table columns.
     *
     * @var array
     */
    private $columns = array();

    /**
     * Table data rows.
     *
     * @var array
     */
    private $rows = array();

    /**
     * Table arguments.
     *
     * @var array
     */
    private $args = array();

    /**
     * Current page.
     *
     * @var int
     */
    private $current_page = 1;

    /**
     * Items per page.
     *
     * @var int
     */
    private $per_page = 20;

    /**
     * Total items.
     *
     * @var int
     */
    private $total_items = 0;

    /**
     * Search term.
     *
     * @var string
     */
    private $search = '';

    /**
     * Sort column.
     *
     * @var string
     */
    private $sort_by = '';

    /**
     * Sort order.
     *
     * @var string
     */
    private $sort_order = 'asc';

    /**
     * Bulk actions.
     *
     * @var array
     */
    private $bulk_actions = array();

    /**
     * Constructor.
     *
     * @param array $columns Column definitions.
     * @param array $args Table arguments.
     */
    public function __construct($columns = array(), $args = array()) {
        $this->columns = $columns;
        $this->args = wp_parse_args($args, array(
            'id' => 'b2b-table',
            'class' => 'b2b-table',
            'empty_message' => 'داده‌ای موجود نیست.',
            'sortable' => true,
            'searchable' => true,
            'pagination' => true,
        ));

        $this->current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $this->per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 20;
        $this->search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
        $this->sort_by = isset($_GET['orderby']) ? sanitize_text_field(wp_unslash($_GET['orderby'])) : '';
        $this->sort_order = isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'asc';
    }

    /**
     * Set table data.
     *
     * @param array $rows Table rows data.
     * @param int $total Total items count.
     */
    public function set_data($rows, $total = null) {
        $this->rows = $rows;
        $this->total_items = ($total !== null) ? $total : count($rows);
    }

    /**
     * Add a bulk action.
     *
     * @param string $action Action slug.
     * @param string $label Action label.
     */
    public function add_bulk_action($action, $label) {
        $this->bulk_actions[$action] = $label;
    }

    /**
     * Get current page.
     *
     * @return int Current page number.
     */
    public function get_current_page() {
        return $this->current_page;
    }

    /**
     * Get per page.
     *
     * @return int Items per page.
     */
    public function get_per_page() {
        return $this->per_page;
    }

    /**
     * Get search term.
     *
     * @return string Search term.
     */
    public function get_search() {
        return $this->search;
    }

    /**
     * Get sort column.
     *
     * @return string Sort column.
     */
    public function get_sort_by() {
        return $this->sort_by;
    }

    /**
     * Get sort order.
     *
     * @return string Sort order.
     */
    public function get_sort_order() {
        return $this->sort_order;
    }

    /**
     * Get offset for SQL query.
     *
     * @return int Offset value.
     */
    public function get_offset() {
        return ($this->current_page - 1) * $this->per_page;
    }

    /**
     * Render the table.
     */
    public function render() {
        echo '<div class="b2b-table-wrap">';

        // Search box.
        if ($this->args['searchable']) {
            $this->render_search();
        }

        // Bulk actions.
        if (!empty($this->bulk_actions)) {
            $this->render_bulk_actions();
        }

        echo '<table class="' . esc_attr($this->args['class']) . '" id="' . esc_attr($this->args['id']) . '">';

        // Header.
        $this->render_header();

        // Body.
        $this->render_body();

        echo '</table>';

        // Pagination.
        if ($this->args['pagination'] && $this->total_items > $this->per_page) {
            $this->render_pagination();
        }

        echo '</div>';
    }

    /**
     * Render search box.
     */
    private function render_search() {
        $current_url = remove_query_arg(array('s', 'paged'));
        echo '<div class="b2b-table-search">';
        echo '<form method="get" action="' . esc_url($current_url) . '">';
        if (isset($_GET['page'])) {
            echo '<input type="hidden" name="page" value="' . esc_attr(sanitize_text_field(wp_unslash($_GET['page']))) . '" />';
        }
        echo '<div class="b2b-search-box">';
        echo '<input type="search" name="s" value="' . esc_attr($this->search) . '" class="b2b-search-input" placeholder="جستجو..." />';
        echo '<button type="submit" class="b2b-btn b2b-btn-primary">جستجو</button>';
        if ($this->search) {
            $clear_url = remove_query_arg('s');
            echo '<a href="' . esc_url($clear_url) . '" class="b2b-btn b2b-btn-link">پاک کردن</a>';
        }
        echo '</div>';
        echo '</form>';
        echo '</div>';
    }

    /**
     * Render bulk actions.
     */
    private function render_bulk_actions() {
        echo '<div class="b2b-table-bulk">';
        echo '<select name="bulk_action" class="b2b-select b2b-bulk-select">';
        echo '<option value="">عملیات گروهی</option>';
        foreach ($this->bulk_actions as $action => $label) {
            echo '<option value="' . esc_attr($action) . '">' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<button type="button" class="b2b-btn b2b-btn-secondary b2b-bulk-submit">اعمال</button>';
        echo '</div>';
    }

    /**
     * Render table header.
     */
    private function render_header() {
        echo '<thead><tr>';

        // Bulk checkbox column.
        if (!empty($this->bulk_actions)) {
            echo '<th class="b2b-col-check"><input type="checkbox" class="b2b-check-all" /></th>';
        }

        foreach ($this->columns as $key => $col) {
            $th_class = isset($col['class']) ? ' class="' . esc_attr($col['class']) . '"' : '';
            $th_style = isset($col['style']) ? ' style="' . esc_attr($col['style']) . '"' : '';

            $sort_html = '';
            if ($this->args['sortable'] && !empty($col['sortable'])) {
                $sort_url = $this->get_sort_url($key);
                $sort_class = '';
                if ($this->sort_by === $key) {
                    $sort_class = $this->sort_order === 'asc' ? ' b2b-sort-asc' : ' b2b-sort-desc';
                }
                $sort_html = ' <a href="' . esc_url($sort_url) . '" class="b2b-sort-link' . $sort_class . '"><span class="b2b-sort-icon"></span></a>';
            }

            echo '<th' . $th_class . $th_style . '>';
            echo esc_html($col['label']);
            echo $sort_html;
            echo '</th>';
        }

        echo '</tr></thead>';
    }

    /**
     * Render table body.
     */
    private function render_body() {
        echo '<tbody>';

        if (empty($this->rows)) {
            $colspan = count($this->columns);
            if (!empty($this->bulk_actions)) {
                $colspan++;
            }
            echo '<tr><td colspan="' . $colspan . '" class="b2b-table-empty">';
            echo esc_html($this->args['empty_message']);
            echo '</td></tr>';
        } else {
            foreach ($this->rows as $row_index => $row) {
                echo '<tr>';
                if (!empty($this->bulk_actions)) {
                    $row_id = isset($row['ID']) ? $row['ID'] : $row_index;
                    echo '<td class="b2b-col-check"><input type="checkbox" name="bulk_ids[]" value="' . esc_attr($row_id) . '" class="b2b-row-check" /></td>';
                }
                foreach ($this->columns as $key => $col) {
                    $td_class = isset($col['class']) ? ' class="' . esc_attr($col['class']) . '"' : '';
                    $value = isset($row[$key]) ? $row[$key] : '';
                    echo '<td' . $td_class . '>' . wp_kses_post($value) . '</td>';
                }
                echo '</tr>';
            }
        }

        echo '</tbody>';
    }

    /**
     * Render pagination.
     */
    private function render_pagination() {
        $total_pages = ceil($this->total_items / $this->per_page);
        $current_url = remove_query_arg('paged');

        echo '<div class="b2b-table-pagination">';
        echo '<div class="b2b-pagination-info">';
        echo 'نمایش ' . (($this->current_page - 1) * $this->per_page + 1) . ' تا ' . min($this->current_page * $this->per_page, $this->total_items) . ' از ' . $this->total_items . ' مورد';
        echo '</div>';
        echo '<div class="b2b-pagination-links">';

        if ($this->current_page > 1) {
            $prev_url = add_query_arg('paged', $this->current_page - 1, $current_url);
            echo '<a href="' . esc_url($prev_url) . '" class="b2b-page-link">&laquo; قبلی</a>';
        }

        for ($i = 1; $i <= $total_pages; $i++) {
            $page_url = add_query_arg('paged', $i, $current_url);
            $active = ($i === $this->current_page) ? ' b2b-page-active' : '';
            echo '<a href="' . esc_url($page_url) . '" class="b2b-page-link' . $active . '">' . $i . '</a>';
        }

        if ($this->current_page < $total_pages) {
            $next_url = add_query_arg('paged', $this->current_page + 1, $current_url);
            echo '<a href="' . esc_url($next_url) . '" class="b2b-page-link">بعدی &raquo;</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    /**
     * Get sort URL for a column.
     *
     * @param string $column Column key.
     * @return string Sort URL.
     */
    private function get_sort_url($column) {
        $order = 'asc';
        if ($this->sort_by === $column && $this->sort_order === 'asc') {
            $order = 'desc';
        }
        return add_query_arg(array(
            'orderby' => $column,
            'order' => $order,
        ));
    }
}
