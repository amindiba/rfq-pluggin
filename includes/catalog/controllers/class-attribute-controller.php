<?php
defined('ABSPATH') || exit;

class B2B_Attribute_Controller {

    private $service;

    public function __construct() {
        $this->service = new B2B_Attribute_Service();
    }

    public function index($request) {
        $args = array(
            'search' => sanitize_text_field($request->get_param('search') ?? ''),
            'type' => sanitize_text_field($request->get_param('type') ?? ''),
            'per_page' => intval($request->get_param('per_page') ?? 20),
            'page' => intval($request->get_param('page') ?? 1),
        );

        $result = $this->service->get_attributes($args);
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
        $item = $this->service->get_attribute($id);

        if (!$item) {
            return new WP_REST_Response(array('code' => 'not_found', 'message' => 'ویژگی یافت نشد'), 404);
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
