<?php if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}



/**
 * Event Espresso
 * Event Registration and Ticketing Management Plugin for WordPress
 * @ package            Event Espresso
 * @ author                Event Espresso
 * @ copyright        (c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license            http://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link                    http://www.eventespresso.com
 * @ version            $VID:$
 * ------------------------------------------------------------------------
 */
class EEDCoreRestApiTest extends EE_REST_TestCase
{

    /**
     * @group 9222
     */
    public function testGetEeRouteData()
    {
        //assert that there are no write endpoints for wp-core models
        $ee_routes_for_each_version = EED_Core_Rest_Api::get_ee_route_data();
        foreach ($ee_routes_for_each_version as $version => $ee_routes) {
            foreach (EED_Core_Rest_Api::model_names_with_plural_routes('4.8.36') as $model_name => $model_classname) {
                $model = EE_Registry::instance()->load_model($model_name);
                $plural_model_route = EED_Core_Rest_Api::get_plural_route_to($model_name);
                $singular_model_route = EED_Core_Rest_Api::get_singular_route_to($model_name, '(?P<id>\d+)');
                //currently, we expose models even for wp core routes to reading (we have plans to change this though)
                //on https://events.codebasehq.com/projects/event-espresso/tickets/9583
                $this->assertArrayHasKey(
                    0,
                    $ee_routes[$plural_model_route],
                    $plural_model_route
                );
                //now let's double-check the singular routes too
                $this->assertArrayHasKey(
                    0,
                    $ee_routes[$singular_model_route],
                    $singular_model_route
                );
                //wp core models should NOT have write endpoints
                if ($model->is_wp_core_model()) {
                    //make sure there is no insert endpoint
                    $this->AssertArrayNotHasKey(
                        1,
                        $ee_routes[$plural_model_route]
                    );
                    //make sure there is no update or delete endpoints
                    $this->AssertArrayNotHasKey(
                        1,
                        $ee_routes[$singular_model_route]
                    );
                    $this->AssertArrayNotHasKey(
                        2,
                        $ee_routes[$singular_model_route]
                    );
                } else {
                    //make sure there is an insert endpoint
                    $this->AssertArrayHasKey(
                        1,
                        $ee_routes[$plural_model_route]
                    );
                    //make sure there is update and delete endpoints
                    $this->assertArrayHasKey(
                        1,
                        $ee_routes[$singular_model_route]
                    );
                    $this->assertArrayHasKey(
                        2,
                        $ee_routes[$singular_model_route]
                    );
                }
            }
        }
    }



    /**
     * @return array{
     * @type EEM_Base $model
     * }
     */
    public function dataProviderForTestGetAllPluralRoutes()
    {
        $unit_test_data = array();
        foreach (array_keys(EED_Core_Rest_Api::model_names_with_plural_routes('4.8.36')) as $model_name) {
            $model = EE_Registry::instance()->load_model($model_name);
            //lets only verify requests for models with primary keys
            if ($model->has_primary_key_field()) {
                $unit_test_data[$model_name] = array($model);
            }
        }
        return $unit_test_data;
    }



    /**
     * Verifies that, for each model from the data provider, we can query its GET routes
     *
     * @dataProvider dataProviderForTestGetAllPluralRoutes
     * @param EEM_Base $model
     * @group        big_rest_tests
     */
    public function testGetAllPluralRoutes(EEM_Base $model)
    {
        $this->authenticate_as_admin();
        //make sure there's an entry for this model. We will use it in an assertion later
        $model_obj = $this->getAModelObjOfType($model);
        $route = EED_Core_Rest_Api::get_versioned_route_to(
            EED_Core_Rest_Api::get_plural_route_to($model->get_this_model_name()),
            '4.8.36'
        );
        $response = rest_do_request(
            new WP_REST_Request(
                'GET',
                $route
            )
        );
        $response_data = $response->get_data();
        $this->assertNotFalse($response_data);
        $this->assertArrayNotHasKey(
            'code',
            $response_data,
            sprintf(
                'Got error response "%1$s" while querying route "%2$s"',
                wp_json_encode($response_data),
                $route
            )
        );
        //verify we find the item we identified using the models
        $contains_item = false;
        foreach ($response_data as $datum) {
            if ($datum[$model->primary_key_name()] == $model_obj->ID()) {
                $contains_item = true;
                break;
            }
        }
        $this->assertTrue($contains_item);
    }



    /**
     * Verifies that all our models' singular GET routes work
     *
     * @dataProvider dataProviderForTestGetAllPluralRoutes
     * @param EEM_Base $model
     * @group        big_rest_tests
     */
    public function testGetAllSingularRoutes(EEM_Base $model)
    {
        $this->authenticate_as_admin();
        //make sure there's an entry for this model. We will use it in an assertion later
        $model_obj = $this->getAModelObjOfType($model);
        $route = EED_Core_Rest_Api::get_versioned_route_to(
            EED_Core_Rest_Api::get_singular_route_to($model->get_this_model_name(), $model_obj->ID()),
            '4.8.36'
        );
        $response = rest_do_request(
            new WP_REST_Request(
                'GET',
                $route
            )
        );
        $response_data = $response->get_data();
        $this->assertNotFalse($response_data);
        $this->assertArrayNotHasKey(
            'code',
            $response_data,
            sprintf('Got error response "%1$s" while querying route "%2$s"',
                wp_json_encode($response_data),
                $route
            )
        );
        //verify we find the item we identified using the models
        $this->assertEquals($model_obj->ID(), $response_data[$model->primary_key_name()]);
    }



    /**
     * @return array{
     * @type EEM_Base $model
     * @type EE_Model_Relation_Base $relation_obj
     * }
     */
    public function dataProviderForTestGetAllRelatedRoutes()
    {
        $unit_test_data = array();
        $models_with_plural_routes = array_keys(EED_Core_Rest_Api::model_names_with_plural_routes('4.8.36'));
        foreach ($models_with_plural_routes as $model_name) {
            $model = EE_Registry::instance()->load_model($model_name);
            foreach ($model->relation_settings() as $relation_name => $relation_obj) {
                //lets only verify requests for models with primary keys
                if ($model->has_primary_key_field()) {
                    $unit_test_data[$model_name] = array($model, $relation_obj);
                }
            }
        }
        return $unit_test_data;
    }



    /**
     * Verifies that all the existing related routes are queryable
     *
     * @dataProvider dataProviderForTestGetAllRelatedRoutes
     * @param EEM_Base $model
     * $param EE_Model_Relation_Base $relation_obj
     * @group        big_rest_tests
     */
    public function testGetAllRelatedRoutes(EEM_Base $model, EE_Model_Relation_Base $relation_obj)
    {
        $related_model = $relation_obj->get_other_model();
        $this->authenticate_as_admin();
        $model_obj = $this->getAModelObjOfType($model);
        $related_model_obj = $this->getAModelObjOfType($related_model);
        $model_obj->_add_relation_to($related_model_obj, $related_model->get_this_model_name());

        $route = EED_Core_Rest_Api::get_versioned_route_to(
            EED_Core_Rest_Api::get_related_route_to(
                $model->get_this_model_name(),
                $model_obj->ID(),
                $relation_obj
            ),
            '4.8.36'
        );
        $response = rest_do_request(
            new WP_REST_Request(
                'GET',
                $route
            )
        );
        $response_data = $response->get_data();
        $this->assertNotFalse($response_data);
        $this->assertNotEmpty($response_data);
        $this->assertNotNull($response_data);
        $this->assertArrayNotHasKey(
            'code',
            $response_data,
            sprintf(
                'Got error response "%1$s" while querying route "%2$s"',
                wp_json_encode($response_data),
                $route
            )
        );
        if( $relation_obj instanceof EE_Belongs_To_Relation){
            //only expect one result
            $this->assertEquals($related_model_obj->ID(), $response_data[$related_model->primary_key_name()]);
        } else {
            //verify we find the item we identified using the models
            $contains_item = false;
            foreach ($response_data as $datum) {
                if ($datum[$related_model->primary_key_name()] == $related_model_obj->ID()) {
                    $contains_item = true;
                    break;
                }
            }
            $this->assertTrue($contains_item);
        }
    }



    /**
     * Returns an array of model names which an object of type $model
     * needs in order for queries for that model to return anything.
     * (Because there are default model conditions that always join to that model
     * when capabilities are being applied).
     *
     * @param string $model_name
     * @return array
     */
    protected function requiredRelationsInOrderToQuery($model_name)
    {
        $requirements = array(
            'Ticket_Template' => array('Ticket'),
        );
        if (isset($requirements[$model_name])) {
            return $requirements[$model_name];
        } else {
            return array();
        }
    }



    /**
     * Fetches a model object of the specified type, or if none exists creates one.
     * Also, verifies that model object has any related model objects which are
     * needed in order to find that method object when applying caps
     * (because capability conditions might join to that table)
     *
     * @param EEM_Base $model
     * @return EE_Base_Class
     */
    protected function getAModelObjOfType(EEM_Base $model)
    {
        $model_obj = $model->get_one(
            array(
                'caps' => EEM_Base::caps_read,
            )
        );
        if (! $model_obj instanceof EE_Base_Class) {
            $model_obj = $this->new_model_obj_with_dependencies($model->get_this_model_name());
        }
        //add any data they might require in order to be queried
        $required_relations = $this->requiredRelationsInOrderToQuery($model->get_this_model_name());
        foreach ($required_relations as $required_dependent_model_name) {
            $related_model_obj = $this->new_model_obj_with_dependencies($required_dependent_model_name);
            $model_obj->_add_relation_to($related_model_obj, $required_dependent_model_name);
        }
        return $model_obj;
    }
}
// End of file EEDCoreRestApiTest.php
// Location: /EEDCoreRestApiTest.php
