<?php

/*
	List Fields
*/
Route::get(array('admin/extend/fields', 'admin/extend/fields/(:num)'), array('before' => 'auth', 'main' => function($page = 1) {
	$vars['messages'] = Notify::read();
	$vars['token'] = Csrf::token();
	$vars['extend'] = Extend::paginate($page, Config::get('meta.posts_per_page'));

	return View::create('extend/fields/index', $vars)
		->partial('header', 'partials/header')
		->partial('footer', 'partials/footer');
}));

/*
	Add Field
*/
Route::get('admin/extend/fields/add', array('before' => 'auth', 'main' => function() {
	$vars['messages'] = Notify::read();
	$vars['token'] = Csrf::token();

	return View::create('extend/fields/add', $vars)
		->partial('header', 'partials/header')
		->partial('footer', 'partials/footer');
}));

Route::post('admin/extend/fields/add', array('before' => 'auth', 'main' => function() {
	$input = Input::get(array('type', 'field', 'key', 'label', 'attributes'));

	if(empty($input['key'])) {
		$input['key'] = $input['label'];
	}

	$input['key'] = slug($input['key'], '_');

	$validator = new Validator($input);

	$validator->add('valid_key', function($str) use($input) {
		return Extend::where('key', '=', $str)
			->where('type', '=', $input['type'])->count() == 0;
	});

	$validator->check('key')
		->is_max(1, __('extend.key_missing'))
		->is_valid_key(__('extend.key_exists'));

	$validator->check('label')
		->is_max(1, __('extend.label_missing'));

	if($errors = $validator->errors()) {
		Input::flash();

		Notify::error($errors);

		return Response::redirect('admin/extend/fields/add');
	}

	if($input['field'] == 'image') {
		$attributes = Json::encode($input['attributes']);
	}
	else if($input['field'] == 'file') {
		$attributes = Json::encode(array(
			'attributes' => array(
				'type' => $input['attributes']['type']
			)
		));
	}
	else {
		$attributes = '';
	}

	Extend::create(array(
		'type' => $input['type'],
		'field' => $input['field'],
		'key' => $input['key'],
		'label' => $input['label'],
		'attributes' => $attributes
	));

	Notify::success(__('extend.field_created'));

	return Response::redirect('admin/extend/fields');
}));

/*
	Edit Field
*/
Route::get('admin/extend/fields/edit/(:num)', array('before' => 'auth', 'main' => function($id) {
	$vars['messages'] = Notify::read();
	$vars['token'] = Csrf::token();

	$extend = Extend::find($id);

	if($extend->attributes) {
		$extend->attributes = Json::decode($extend->attributes);
	}

	$vars['field'] = $extend;

	return View::create('extend/fields/edit', $vars)
		->partial('header', 'partials/header')
		->partial('footer', 'partials/footer');
}));

Route::post('admin/extend/fields/edit/(:num)', array('before' => 'auth', 'main' => function($id) {
	$input = Input::get(array('type', 'field', 'key', 'label', 'attributes'));

	if(empty($input['key'])) {
		$input['key'] = $input['label'];
	}

	$input['key'] = slug($input['key'], '_');

	$validator = new Validator($input);

	$validator->add('valid_key', function($str) use($id, $input) {
		return Extend::where('key', '=', $str)
			->where('type', '=', $input['type'])
			->where('id', '<>', $id)->count() == 0;
	});

	$validator->check('key')
		->is_max(1, __('extend.key_missing'))
		->is_valid_key(__('extend.key_exists'));

	$validator->check('label')
		->is_max(1, __('extend.label_missing'));

	if($errors = $validator->errors()) {
		Input::flash();

		Notify::error($errors);

		return Response::redirect('admin/extend/fields/add');
	}

	if($input['field'] == 'image') {
		$attributes = Json::encode($input['attributes']);
	}
	else if($input['field'] == 'file') {
		$attributes = Json::encode(array(
			'attributes' => array(
				'type' => $input['attributes']['type']
			)
		));
	}
	else {
		$attributes = '';
	}

	Extend::update($id, array(
		'type' => $input['type'],
		'field' => $input['field'],
		'key' => $input['key'],
		'label' => $input['label'],
		'attributes' => $attributes
	));

	Notify::success(__('extend.field_updated'));

	return Response::redirect('admin/extend/fields/edit/' . $id);
}));

/*
	Delete Field
*/
Route::get('admin/extend/fields/delete/(:num)', array('before' => 'auth', 'main' => function($id) {
	$field = Extend::find($id);

	Query::table(Base::table($field->type . '_meta'))->where('extend', '=', $field->id)->delete();

	$field->delete();

	Notify::success(__('extend.field_deleted'));

	return Response::redirect('admin/extend/fields');
}));