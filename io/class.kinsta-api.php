<?php

if(!class_exists('KINSTA_API')){
	class KINSTA_API
	{
		private $rest_api_url;

		private $message_code = [
		    'auth_failed' => [
		            'code' => 401,
                    'message' => 'Authorization failed, Please try again',
            ],
            'listing_update' => [
                'code' => 200,
                'message' => 'Listing update successfully',
            ],
            'listing_created' => [
                'code' => 201,
                'message' => 'Listing created successfully',
            ],
            'listing_delete' => [
                'code' => 200,
                'message' => 'Listing deleted successfully',
            ],
            'no_content' => [
                'code' => 204,
                'message' => 'Listing array is empty',
            ],
            'error_default' => [
                'code' => 400,
                'message' => 'There is a problem with request please try again letter',
            ],
            'success_default' => [
                'code' => 200,
                'message' => 'Request completed successfully',
            ],
            'listing_id_not_found' => [
                'code' => 400,
                'message' => 'Listing ID is required',
            ],
            'listing_not_found' => [
                'code' => 400,
                'message' => 'Listing not found against provided listing id',
            ],
            'listing_not_updated' => [
                'code' => 400,
                'message' => 'Could not update listing please try again letter',
            ]
        ];

		private $key_maps = [

		    'listingId' => 'post_title',
		    'postStatus' => 'post_status',
		    'pointsOnContract' => 'listing_points_contract',
		    'pricePerPoint' => 'listing_points_price',
		    'pointsAvailable' => 'listing_points_available_repeater',
		    'pointsAvailable_year' => 'listing_points_available_year',
		    'pointsAvailable_points' => 'listing_points_available_points',
		    'listingDescription' => 'listing_points_available_description',
		    'resort' => 'listing_resort',
		    'promotion' => '',
		    'featured' => 'featured',
		    'additionalDetails' => 'listing_additional_details',
		    'additionalDetails_label' => 'listing_additional_details_label',
		    'additionalDetails_value' => 'listing_additional_details_value',
		    'annualDues' => 'listing_detail_annual_dues',
		    'deedExpiration' => 'listing_detail_deed',
		    'closingCosts' => 'listing_detail_closing_costs',
		    'closingCostsDescription' => '',
		    'closingCostsPayer' => 'total_cost_closing_costs_payer',
		    'totalDuesPayer' => 'total_cost_total_dues_payer',
		    'duesCredit' => 'total_cost_dues_credit',

        ];

		private $post_keys = [
            'post_title' => 'post_title',
            'post_status' => 'post_status',
        ];

		private $diff_obj = [
		    'listing_resort' => 'post_type|resort',
            'listing_status' => 'taxonomy|statuses',
        ];

		function __construct()
		{
			$this->rest_api_url = 'kinsta/v1';;
			$this->execute_settings_api();
		}

	
		private function validate_auth($authorization){
	
            $status = false;

            if(!empty($authorization)){

                $user_details = base64_decode($authorization);
                list($user_name, $pwd) = explode(':',$user_details);


                $user = get_user_by( 'login', $user_name );

                if ( ! $user && is_email( $user_name ) ) {
                    $user = get_user_by( 'email', $user_name );
                }



                // If the login name is invalid, short circuit.
                if ( $user ) {
                    $status = wp_check_password($pwd, $user->user_pass, $user->ID);
                }

            }

            return $status;
		}

	
		private function execute_settings_api(){

			add_action( 'rest_api_init', array($this, 'register_k_listing_route'));
			add_action('init', array($this, 'test'));
		}
	


		function register_k_listing_route(){

			register_rest_route( $this->rest_api_url, '/listing/', array(
				'methods' => 'PUT',
				'callback' => array($this, 'listing_update_request'),
				'permission_callback' => '__return_true',

			));

            register_rest_route( $this->rest_api_url, '/listing/', array(
                'methods' => 'DELETE',
                'callback' => array($this, 'listing_del'),
                'permission_callback' => '__return_true',

            ));


		}

		private function get_diff_object($list_key, $listing_val){

                $key_type = $this->diff_obj[$list_key];

                list($type, $obj) = explode('|', $key_type);

                switch ($type){
                    case 'post_type':

                        $post_obj = get_page_by_title($listing_val, OBJECT, $obj);
                        if($post_obj){
                            $listing_val = $post_obj->ID;
                        }
                        break;
                    case 'taxonomy':
                        $term = get_term_by('name', $listing_val, $obj);

                        if($term){
                            $listing_val = $term->term_id;
                        }
                        break;

                }

                return $listing_val;

        }

		private function update_listing_db($listing_obj){


            if(!empty($listing_obj)){

                $new_listing_map = array();

                foreach ($listing_obj as $listing_key => $listing_val){

                    $new_listing_key = array_key_exists($listing_key, $this->key_maps) && $this->key_maps[$listing_key] ? $this->key_maps[$listing_key] : $listing_key;

                    if(!is_array($listing_val)){
                        $new_listing_map[$new_listing_key] = $listing_val;
                    }else{
                        if(!empty($listing_val)){
                            foreach ($listing_val as $r_index => $reapeater_group){
                                if(!empty($reapeater_group)){
                                    $new_r_group = array();
                                    foreach($reapeater_group as $r_key => $r_value){
                                        $r_obj_key = $listing_key.'_'.$r_key;
                                        $new_r_key = array_key_exists($r_obj_key, $this->key_maps) ? $this->key_maps[$r_obj_key] : $r_key;
                                        $new_r_group[$new_r_key] = $r_value;
                                    }

                                    $listing_val[$r_index] = $new_r_group;
                                }
                            }

                            $new_listing_map[$new_listing_key] = $listing_val;
                        }
                    }
                }

                $post_args = array_intersect_key($new_listing_map, $this->post_keys);
                $post_title = array_key_exists('post_title', $post_args) ? $post_args['post_title'] : '';

                if($post_title){

                    $post_args['post_type'] = 'listing';
                    $listing_obj = get_page_by_title($post_title, OBJECT, $post_args['post_type']);

                    if($listing_obj && $listing_obj->ID){
                            $post_args['ID'] = $listing_obj->ID;
                            $post_update = wp_update_post($post_args, true);
                            $update_type = 'listing_update';
                    }else{
                            $post_update = wp_insert_post($post_args, true);
                            $update_type = 'listing_created';
                    }


                    $update_result = array();
                    if(!$post_update instanceof WP_Error){

                        if(!empty($new_listing_map)){

                            foreach ($new_listing_map as $list_key => $list_value){

                                if(array_key_exists($list_key, $this->post_keys)){
                                    continue;
                                }

                                if(array_key_exists($list_key, $this->diff_obj)){
                                    $list_value = $this->get_diff_object($list_key, $list_value);
                                }

                                $arr_obj = [
                                    'status' => update_field($list_key, $list_value, $post_update),
                                    $list_key => $list_value,
                                    'post_id' => $post_update,
                                ];
                                $update_result[] = $arr_obj;
                            }

                            return $this->get_code($update_type, 'success_default');
                        }else{
                            return $this->get_code('no_content');
                        }



                    }else{
                        return  $this->get_code('listing_not_updated');
                    }

                    return  ['update_result' => $update_result];

                }else{
                    return $this->get_code('listing_id_not_found');
                }


            }else{
                return $this->get_code('no_content');
            }


            return $resp;

        }

        public function get_code($code, $default = 'error_default'){
		    $resp =  array_key_exists($code, $this->message_code) ? $this->message_code[$code] : array();

		    if(empty($resp)){
                $resp =  array_key_exists($default, $this->message_code) ? $this->message_code[$default] : array();
                $code = $default;
            }

		    if(!empty($resp)){
		        $resp['message_code'] = $code;
            }

		    return $resp;
        }

		function listing_update_request($request){

            $authorization = $request->get_header('Authorization');
            $auth_status = $this->validate_auth($authorization);

            if(!$auth_status){
                $resp = $this->get_code('auth_failed');
            }else{


                $body = $request->get_body();
                $body = json_decode($body, true);
                $resp = $this->update_listing_db($body);


            }

            return new WP_REST_Response($resp, $resp['code']);

        }

        function listing_del($request){

            $authorization = $request->get_header('Authorization');
            $auth_status = $this->validate_auth($authorization);

            if(!$auth_status){
                $resp = $this->get_code('auth_failed');
            }else{


                $body = $request->get_body();
                $body = json_decode($body, true);
                if(is_array($body) && array_key_exists('listingId', $body) && !empty($body['listingId'])){
                    $listing_id = $body['listingId'];
                    $listing = get_page_by_title($listing_id, OBJECT, 'listing');

                    if($listing){
//                        $del_status = wp_delete_post($listing->ID, true);
                        $del_status = wp_trash_post($listing->ID);
                        if(!empty($del_status)){
                            $resp = $this->get_code('listing_delete');
                        }else{
                            $resp = $this->get_code('error_default');
                        }
                    }else{
                        $resp = $this->get_code('listing_not_found');
                    }

                }else{
                    $resp = $this->get_code('listing_id_not_found');
                }
            }

            return new WP_REST_Response($resp, $resp['code']);

        }


        public function test(){
            return;
		    if(!isset($_GET['debug'])){

            }

            $resp = array('update');

            update_field('listing_year', "January", 162894);
            $tst = array(array('listing_additional_details_label' => 'test', 'listing_additional_details_value' => 'test value'), array('listing_additional_details_label' => 'test2', 'listing_additional_details_value' => 'test value2'));
            update_field('listing_additional_details', $tst, 162894);

            $post = get_post(162894);
            $post_meta = get_post_meta(162894);

            $new_meta = array();
            foreach($post_meta as $meta_key => $meta_value){
                $new_meta[$meta_key] = get_post_meta(162894, $meta_key, true);
            }

            $new_post = (array) $post;
            $new_post['post_meta'] = $new_meta;


            $resp = $new_post;

            pree($resp);



            exit;
        }
	
	
    }
}


