<?php
defined('ABSPATH') || exit;

class B2B_Category_Controller {

    private $service;

    public function __construct() {
        $this->service = new B2B_Category_Service();
    }

    public function index($request) {
        $args = array(
            'search' => sanitize_text_field($request->get_param('search') ?? ''),
            'status' => sanitize_text_field($request->get_param('status') ?? ''),
            'per_page' => intval($request->get_param('per_page') ?? 20),
            'page' => intval($request->get_param('page') ?? 1),
            'orderby' => sanitize_text_field($request->get_param('orderby') ?? 'sort_order'),
            'order' => sanitize_text_field($request->get_param('order') ?? 'ASC'),
        );

        $result = $this->service->get_categories($args);
        $data = array();
        foreach ($result['items'] as $item) {
            $data[] = $item->to_array();
        }

        return new WP_REST_Response(array(
            'data' => $data,
            'total' => $result['total'],
            'pages' => $result['pages'],
            'page' => $result['page'],
        ), 200);
    }

    public function show($request) {
        $id = intval($request->get_param('id'));
        $item = $this->service->get_category($id);

        if (!$item) {
            return new WP_REST_Response(array('code' => 'not_found', 'message' => 'دسته‌بندی یافت نشد'), 404);
        }

        return new WP_REST_Response(array('data' => $item->to_array()), 200);
    }

    public function store($request) {
        $data = $request->get_json_params();
        $result = $this->service->create($data);

        if (!$result['success']) {
            return new WP_REST_Response(array('code' => 'validation_error', 'message' => 'خطا در اعتبارسنجی', 'data' => $result['errors']), 400);
        }

        return new WP_REST_Response(array('data' => array('id' => $result['id']), 'message' => $result['message']), 201);
    }

    public function update($request) {
        $id = intval($request->get_param('id'));
        $data = $request->get_json_params();
        $result = $this->service->update($id, $data);

        if (!$result['success']) {
            $code = isset($result['errors']['general']) ? 'not_found' : 'validation_error';
            $status = $code === 'not_found' ? 404 : 400;
            return new WP_REST_Response(array('code' => $code, 'message' => reset($result['errors']), 'data' => $result['errors']), $status);
        }

        return new WP_REST_Response(array('data' => array('id' => $id), 'message' => $result['message']), 200);
    }

    public function destroy($request) {
        $id = intval($request->get_param('id'));
        $permanent = $request->get_param('permanent') === 'true';
        $result = $this->service->delete($id, $permanent);

        if (!$result['success']) {
            return new WP_REST_Response(array('code' => 'error', 'message' => reset($result['errors'])), 400);
        }

        return new WP_REST_Response(array('message' => $result['message']), 200);
    }
}
