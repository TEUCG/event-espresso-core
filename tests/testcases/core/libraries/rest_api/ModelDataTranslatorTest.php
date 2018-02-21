<?php
use EventEspresso\core\libraries\rest_api\ModelDataTranslator;
use EventEspresso\core\libraries\rest_api\RestException;

if (! defined('EVENT_ESPRESSO_VERSION')) {
    exit('No direct script access allowed');
}

/**
 * Class ModelDataTranslator_Test
 * Description here
 *
 * @package               Event Espresso
 * @subpackage
 * @author                Mike Nelson
 *
 * @group                 rest_api
 */
class ModelDataTranslatorTest extends EE_REST_TestCase
{

    /**
     * Contains an array of RFC3339/ISO8601 formatted date strings and the accompanying unix timestamp for them
     */
    public function timestampDataProvider()
    {
        return array(
            array('2018-02-21T06:09:37+00:00', 1519193377, 'UTC'),
            array('2018-02-21T06:09:37+10:00', 1519157377, 'UTC'),
            array('2018-02-21T06:09:37-03:30', 1519205977, 'UTC'),
            //since -3:30 is invalid for the standard (missing a padded 0 after the -), we expect false to get returned
            //for the value.
            array('2018-02-21T06:09:37-3:30', false, 'UTC')
        );
    }


    /**
     * @dataProvider timestampDataProvider
     * @param $timestamp
     * @param $expected_unixtimestamp
     * @param $timezone_string
     * @throws DomainException
     * @throws EE_Error
     * @throws InvalidArgumentException
     * @throws RestException
     * @group 11368
     */
    public function testIncomingTimestampWithTimezoneInformation($timestamp, $expected_unixtimestamp, $timezone_string)
    {
        $this->assertEquals(
            $expected_unixtimestamp,
            ModelDataTranslator::prepareFieldValueFromJson(
                new EE_Datetime_Field(
                    'post_date',
                    esc_html__('Date/Time Event Created', 'event_espresso'),
                    false,
                    EE_Datetime_Field::now
                ),
                $timestamp,
                '4.8.36',
                $timezone_string
            )
        );
    }

    public function testPrepareQueryParamsForRestApi()
    {
        $mysql_date = '2015-01-01 00:00:00';
        $statuses_in_query = array(
            EEM_Registration::status_id_cancelled,
            EEM_Registration::status_id_declined,
        );
        $model_query = array(
            array(
                'STS_ID'      => array(
                    'IN',
                    $statuses_in_query,
                ),
                'REG_date'    => strtotime($mysql_date),
                'REG_deleted' => false,
            ),
            'limit' => 10,
        );
        $rest_query = ModelDataTranslator::prepareQueryParamsForRestApi($model_query, EEM_Registration::instance());
        //assert the reg date matches and is in the right format
        $this->assertArrayHasKey('where', $rest_query);
        $this->assertArrayHasKey('REG_date', $rest_query['where']);
        $this->assertEquals(strtotime($mysql_date), rest_parse_date($rest_query['where']['REG_date']));
        //assert statuses got translated
        $this->assertArrayHasKey('STS_ID', $rest_query['where']);
        $this->assertEquals($statuses_in_query, $rest_query['where']['STS_ID'][1]);
        //assert limit got translated
        $this->assertArrayHasKey('limit', $rest_query);
        $this->assertEquals(10, (int)$rest_query['limit']);
        //assert booleans correctly translated
        $this->assertArrayHasKey('REG_deleted', $rest_query['where']);
        $this->assertEquals(false, $rest_query['where']['REG_deleted']);
    }



    /**
     * Verifies prepare_conditions_query_params_for_models works properly,
     * especially with datetimes which can be in UTC or local time
     */
    public function testPrepareConditionsQueryParamsForModels__gmtDatetimes()
    {
        $data_translator = new ModelDataTranslator();
        $gmt_offsets = array(-12, -10.5, -9, -7.5, -6, -4.5, -3, -1.5, 0, 1.5, 3, 4.5, 6, 7.5, 9, 10.5, 12);
        foreach ($gmt_offsets as $gmt_offset) {
            //set the offset
            update_option('gmt_offset', $gmt_offset);
            $now_local_time = current_time('mysql');
            $now_utc_time = current_time('mysql', true);
            //should always be equal except when offset is 0
            if ($gmt_offset !== 0) {
                $this->assertNotEquals($now_local_time, $now_utc_time, sprintf('For gmt offset %d', $gmt_offset));
            } else {
                $this->assertEquals($now_local_time, $now_utc_time);
            }
            $model_data = $data_translator::prepareConditionsQueryParamsForModels(
                array(
                    'EVT_created'      => mysql_to_rfc3339($now_local_time),
                    'EVT_modified_gmt' => mysql_to_rfc3339($now_utc_time),
                ),
                \EEM_Event::instance(),
                '4.8.36'
            );
            //verify the model data being inputted is in UTC
            $this->assertEquals($now_utc_time, date('Y-m-d H:i:s', $model_data['EVT_created']));
            //NOT in local time
            $this->assertNotEquals(
                $now_local_time,
                $model_data['EVT_created'],
                sprintf('For gmt offset %d', $gmt_offset)
            );
            //notice that there's no "_gmt" on EVT_modified. That's (currently at least)
            //not a real model field. It just indicates to treat the time already being in UTC
            $this->assertEquals($now_utc_time, date('Y-m-d H:i:s', $model_data['EVT_modified']));
        }
    }



    public function testIsGmtDateFieldName__success()
    {
        $this->assertTrue(ModelDataTranslator::isGmtDateFieldName('Event.EVT_created_gmt'));
    }



    public function testIsGmtDateFieldName__fail()
    {
        $this->assertFalse(ModelDataTranslator::isGmtDateFieldName('Event.EVT_created'));
    }



    public function testIsGmtDateFieldName__failTinyInput()
    {
        $this->assertFalse(ModelDataTranslator::isGmtDateFieldName('foo'));
    }



    public function testRemoveGmtFromFieldName()
    {
        $this->assertEquals(
            'Event.EVT_created',
            ModelDataTranslator::removeGmtFromFieldName('Event.EVT_created_gmt'));
    }



    public function testRemoveGmtFromFieldName__noGmtAnyways()
    {
        $this->assertEquals(
            'Event.EVT_created',
            ModelDataTranslator::removeGmtFromFieldName('Event.EVT_created'));
    }



    /**
     * @return array first item is the expected value, 2nd is the input, 3rd is the field object to use
     */
    public function dataProviderForTestPrepareFieldValueFromJsonOk()
    {
        $serialized_field = new EE_Maybe_Serialized_Simple_HTML_Field('whatever', 'Whatever', true);
        return array(
            array('1', '1', $serialized_field),
            array('stringy', 'stringy', $serialized_field),
            array(array('foo' => 'bar'), array('foo' => 'bar'), $serialized_field),
        );
    }



    /**
     * @dataProvider dataProviderForTestPrepareFieldValueFromJsonOk
     * @param mixed $expected_result
     * @param mixed $inputted_json_value
     * @param EE_Model_Field_Base $field_obj
     * @group        9222
     */
    public function testPrepareFieldValueFromJsonOk(
        $expected_result,
        $inputted_json_value,
        EE_Model_Field_Base $field_obj
    ) {
        $this->assertEquals(
            $expected_result,
            ModelDataTranslator::prepareFieldValueFromJson(
                $field_obj,
                $inputted_json_value,
                '4.8.36'
            )
        );
    }



    /**
     * @return array where the first item is value that would be retrieved from the request which should throw an
     * exception. The 2nd item is an EE_Model_Field_Base child
     */
    public function dataProviderForTestPrepareFieldValueFromJsonBad()
    {
        $serializable_field = new EE_Maybe_Serialized_Simple_HTML_Field('whatever', 'Whatever', true);
        $serializable_field->_construct_finalize('Foobar', 'test_serialized_field','Foobar');
        $text_field = new EE_Plain_Text_Field('whatever', 'whatever', true);
        $text_field->_construct_finalize('Foobar', 'test_text_field','Foobar');
        return array(
            array('s:6:"foobar";', $serializable_field),//that's a serialized string alright!
            array('O:4:"Evil":0:{}', $serializable_field),//that's a string with a serialized object of class "Evil"
            array(array('s:6:"foobar";'), $serializable_field),//that's an array with a serialized string in it
            array(array('s:6:"foobar";' => 1), $serializable_field),//that's an array with a serialized string as a key
            array('O:4:"Evil":0:{}', $text_field),//double-check we don't even accept serialized text even on normal
            // text fields. Theoretically these won't get unserialized, but I don't see much need for anyone to ever
            // submit this kind of malicious junk, and having them sit around in our DB is dangerous
            array(
                array(
                  'error_code' => 'php_object_not_return',
                  'error_message' => esc_html__(
                      'The value of this field in the database is a PHP object, which can\'t be represented in JSON.',
                      'event_espresso'
                  )
                ),
                $serializable_field
            )
        );
    }



    /**
     * @dataProvider dataProviderForTestPrepareFieldValueFromJsonBad
     * @expectedException EventEspresso\core\libraries\rest_api\RestException
     * @param mixed $expected_result
     * @param mixed $inputted_json_value
     * @param EE_Model_Field_Base $field_obj
     * @group 9222
     */
    public function testPrepareFieldValueFromJsonBad($inputted_json_value, EE_Model_Field_Base $field_obj)
    {
        //ok duck and cover! It's gonna blow!
        ModelDataTranslator::prepareFieldValueFromJson($field_obj, $inputted_json_value, '4.8.36');
    }



    /**
     * @return array 1st item is the expected value, 2nd is the input, 3rd is the field object to use
     */
    public function dataProviderForTestPrepareFieldValuesForJson()
    {
        //if we don't have the WP API infrastructure, return a dummy array
        //it doesn't have to work because all the tests will be skipped too
        if ( ! class_exists( 'WP_Rest_Request' ) ) {
            return array(
                array()
            );
        }
        $field = new EE_Maybe_Serialized_Simple_HTML_Field('whatever', 'whatever', true);
        $datetime_field = new EE_Datetime_Field('whatever2', 'whatever2', true, EE_Datetime_Field::now);
        $error_response = array(
            'error_code' => 'php_object_not_return',
            'error_message' => esc_html__(
                'The value of this field in the database is a PHP object, which can\'t be represented in JSON.',
                'event_espresso'
            )
        );
        $datetime_field_obj = EE_Registry::instance()->load_model('Datetime')->field_settings_for('DTT_EVT_start');
        return array(
            array(
                array('foo' => 'bar'),
                array('foo' => 'bar'),
                $field)
            ,
            array(
                1,
                1,
                $field
            ),
            array(
                'stringy',
                'stringy',
                $field
            ),
            array(
                '2016-01-03T00:00:00',
                new \EventEspresso\core\domain\entities\DbSafeDateTime(
                    '2016-01-03 00:00:00',
                    new DateTimeZone('UTC')
                ),
                $datetime_field,
            ),
            array(
                $error_response,
                new stdClass(),
                $field
            ),
            array(
                array('obj' => $error_response),
                array('obj' => new stdClass()),
                $field
            ),
            array(
                $error_response,
                @unserialize('O:6:"Foobar":0:{}'),
                $field
            ),
            'datetime_object_in_default_timezone'       => array(
                mysql_to_rfc3339(current_time('mysql')),
                new DateTime('now'),
                $datetime_field_obj,
            ),
            'unix_timestamp_in_default_timezone'        => array(
                mysql_to_rfc3339(date(EE_Datetime_Field::mysql_timestamp_format, 946782245)),
                946782245,
                $datetime_field_obj,
            ),
            'unix_timestamp_STRING_in_default_timezone' => array(
                mysql_to_rfc3339(date(EE_Datetime_Field::mysql_timestamp_format, 946782245)),
                '946782245',
                $datetime_field_obj,
            ),
            'unix_timestamp_FLOAT_in_default_timezone' => array(
                mysql_to_rfc3339(date(EE_Datetime_Field::mysql_timestamp_format, 946782245)),
                (float)946782245,
                $datetime_field_obj,
            ),
            'null_datetime'                             => array(
                '',
                null,
                $datetime_field_obj,
            ),
            'mysql_in_default_timezone'                 => array(
                mysql_to_rfc3339('2000-01-02 03:04:05'),
                '2000-01-02 03:04:05',
                $datetime_field_obj,
            ),
            'datetime_object_in_different_timezone'     => array(
                mysql_to_rfc3339('2000-01-02 03:04:05'),
                new DateTime('2000-01-02 03:04:05', new DateTimeZone('America/Vancouver')),
                $datetime_field_obj,
                'America/Vancouver',
            ),
            //the input is a unix timestamp (in GMT)
            //the result should be a RFC3339 string also in GMT
            'unix_timestamp_in_different_timezone'      => array(
                mysql_to_rfc3339('2000-01-02 03:04:05'),
                946782245,
                $datetime_field_obj,
                'America/Vancouver',
            ),
            //so the input is for 3am Vancouver time, and the output should be too
            'mysql_datetime_in_different_timezone'      => array(
                mysql_to_rfc3339('2000-01-02 03:04:05'),
                '2000-01-02 3:04:05',
                $datetime_field_obj,
                'America/Vancouver',
            ),
        );
    }



    /**
     * @group        9222
     * @dataProvider dataProviderForTestPrepareFieldValuesForJson
     * @param                     $expected
     * @param                     $input
     * @param EE_Model_Field_Base $field_obj
     */
    public function testPrepareFieldValuesForJson($expected, $input, $field_obj)
    {
        $this->assertEquals(
            $expected,
            ModelDataTranslator::prepareFieldValuesForJson($field_obj, $input, '4.8.36')
        );
    }



    /**
     * @return array {
     * @type array $0 input
     * @type string $1 the model's name
     * @type boolean $2 whether to consider this data as if it's being used for writing, or not
     */
    public function dataProviderForTestPrepareConditionsQueryParamsForModelsBad()
    {
        return array(
            //case 0: array isn't numerically indexed
            array(
                array(
                    'EVT_ID' => array(
                        'what_is_this_key_doing_here' => 'its_borked'
                    )
                ),
                'Event',
                false
            ),
            //case 1: invalid key while reading
            array(
                array(
                    'invalid_key' => 'whatever'
                ),
                'Event',
                false
            ),
            //case 2: invalid key while writing
            array(
                array(
                    'invalid_key' => 'whatever'
                ),
                'Registration',
                true
            ),
            //case 3: logic parameter while writing
            array(
                array(
                    'OR' => array(
                        'EVT_name' => 'party'
                    )
                ),
                'Event',
                true
            ),
            //case 4: nested invalid key while reading
            array(
                array(
                    'or*allyourbase' => array(
                        'EVT_ID' => 123,
                        'invalid_nested_key' => 'foobar'
                    )
                ),
                'Event',
                false
            ),
            //case 5: too few arguments for in operator
            array(
                array(
                    'EVT_ID' => array('IN')
                ),
                'Event',
                false
            ),
            //case 6: too many arguments for in operator
            array(
                array(
                    'EVT_ID' => array('IN', array('thingy'),'what_is_this_doing_here')
                ),
                'Event',
                false
            ),
            //case 7: too few arguments for between operator
            array(
                array(
                    'EVT_created' => array(
                        'BETWEEN',
                        '2017-01-01T00:00:00'
                    )
                ),
                'Event',
                false
            ),
            //case 8: too many arguments for between operator
            array(
                array(
                    'EVT_created' => array(
                        'between',
                        '2017-01-01T00:00:00',
                        '2018-01-01T00:00:00',
                        'extra_junk'
                    )
                ),
                'Event',
                false
            ),
            //case 9: too few arguments for like operator
            array(
                array(
                    'EVT_name' => array(
                        'LIKE'
                    )
                ),
                'Event',
                false
            ),
            //case 10: too many arguments for like operator
            array(
                array(
                    'EVT_name' => array(
                        'LIKE',
                        'foobar',
                        'something_extra'
                    )
                ),
                'Event',
                false
            ),
            //case 11: too few arguments for normal operator
            array(
                array(
                    'EVT_ID' => array(
                        '>'
                    )
                ),
                'Event',
                false
            ),
            //case 12: too many arguments for normal operator
            array(
                array(
                    'EVT_ID' => array(
                        '<',
                        123,
                        23452343
                    )
                ),
                'Event',
                false
            ),
            //case 13: too many arguments for null operator
            array(
                array(
                    'EVT_ID' => array(
                        'IS_NULL',
                        'what_is_this_extra_arg'
                    )
                ),
                'Event',
                false
            )
        );
    }



    /**
     * @param array $input array of data sent to REST API
     * @param string $model_name eg 'Event'
     * @param boolean $writing
     * @group        9222
     * @dataProvider dataProviderForTestPrepareConditionsQueryParamsForModelsBad
     * @expectedException EventEspresso\core\libraries\rest_api\RestException
     */
    public function testPrepareConditionsQueryParamsForModelsBad($input, $model_name, $writing)
    {
        $model = EE_Registry::instance()->load_model($model_name);
        //run for cover! it's going to error!
        ModelDataTranslator::prepareConditionsQueryParamsForModels(
            $input,
            $model,
            '4.8.36',
            $writing
        );
    }



    /**
     * @return array {
     * @type array $0 expected output
     * @type array $1 input
     * @type string $2 model name, eg 'Event'
     * @type boolean $3 whether it's data for writing, or just conditions
     */
    public function dataProviderForTestPrepareConditionsQueryParamsForModelsGood()
    {
        if( ! function_exists('rest_parse_date')){
            return array();
        }
        return array(
            //reading-style tests
            //case 0: empty
            array(
                array(),
                array(),
                'Event',
                false
            ),
            //case 1: simple
            array(
                array('EVT_name' => 'foobar'),
                array('EVT_name' => 'foobar'),
                'Event',
                false
            ),
            //case 2: with nested logic
            array(
                array(
                    'or' => array(
                        'EVT_desc' => 'foobar',
                        'EVT_short_desc' => 'foobar'
                    ),
                    'NOT*' => array(
                        'EVT_name' => 'foobar'
                    )
                ),
                array(
                    'or' => array(
                        'EVT_desc' => 'foobar',
                        'EVT_short_desc' => 'foobar'
                    ),
                    'NOT*' => array(
                        'EVT_name' => 'foobar'
                    )
                ),
                'Event',
                false
            ),
            //case 3: with a between operator
            array(
                array(
                    'EVT_created' => array(
                        'between',
                        rest_parse_date('2015-01-01 00:00:00'),
                        rest_parse_date('2016-01-01 00:00:00')
                    )
                ),
                array(
                    'EVT_created' => array(
                        'between',
                        '2015-01-01T00:00:00',
                        '2016-01-01T00:00:00'
                    )
                ),
                'Event',
                false
            ),
            //case 4: with an "in" operator
            array(
                array(
                    'TKT_uses' => array(
                        'IN',
                        array(
                            12,
                            13,
                            EE_INF
                        )
                    )
                ),
                array(
                    'TKT_uses' => array(
                        'IN',
                        array(
                            '12',
                            '13',
                            ''
                        )
                    )
                ),
                'Ticket',
                false
            ),
            //case 5: with a "like" operator
            array(
                array(
                    'PAY_details' => array(
                        'LIKE',
                        '%foobar%'
                    )
                ),
                array(
                    'PAY_details' => array(
                        'LIKE',
                        '%foobar%'
                    )
                ),
                'Payment',
                false
            ),
            //case 6: with a "null" operator
            array(
                array(
                    'EVT_name' => array('IS_NULL')
                ),
                array(
                    'EVT_name' => array('IS_NULL')
                ),
                'Event',
                false
            ),
            //case 7: with various other operators
            array(
                array(
                    'RPY_amount' => array(
                        '<',
                        19
                    ),
                    'REG_ID' => array(
                        '!=',
                        12
                    ),
                ),
                array(
                    'RPY_amount' => array(
                        '<',
                        '19'
                    ),
                    'REG_ID' => array(
                        '!=',
                        '12'
                    ),
                ),
                'Registration_Payment',
                false
            ),
            //case 8: using related fields
            array(
                array(
                    'Ticket.Datetime.DTT_reg_limit' => 12
                ),
                array(
                    'Ticket.Datetime.DTT_reg_limit' => '12'
                ),
                'Registration',
                false
            ),
            //writing-style tests
            //case 8: with valid fields
            array(
                array(
                    'TKT_uses' => EE_INF
                ),
                array(
                    'TKT_uses' => ''
                ),
                'Ticket',
                true
            )
        );
    }

    /**
     * @param array $expected_output
     * @param array $input array of data sent to REST API
     * @param string $model_name eg 'Event'
     * @param boolean $writing
     * @group        9222
     * @dataProvider dataProviderForTestPrepareConditionsQueryParamsForModelsGood
     */
    public function testPrepareConditionsQueryParamsForModelsGood(
        $expected_output,
        $input,
        $model_name,
        $writing
    ) {
        $model = EE_Registry::instance()->load_model($model_name);
        $this->assertEquals(
            $expected_output,
            ModelDataTranslator::prepareConditionsQueryParamsForModels(
                $input,
                $model,
                '4.8.36',
                $writing
            )
        );
    }

    /**
     * Reproduced issue https://events.codebasehq.com/projects/event-espresso/tickets/10869
     * and https://events.codebasehq.com/projects/event-espresso/tickets/10858 by changing
     * the site date format to 'd/m/Y'
     */
    public function testPrepareFieldValueForJsonUnusualDateFormat(){
        $field_obj = EE_Registry::instance()->load_model('Datetime')->field_settings_for('DTT_EVT_start');
        if($field_obj instanceof EE_Datetime_Field){
            //change the date format because it used to make this not work
            $field_obj->set_date_format('d/m/Y');
            //the default time format excludes seconds,
            $field_obj->set_time_format('g:i a s');
        }
        $current_time_mysql = current_time('mysql');
        $datetime = new DateTime( $current_time_mysql );
        $formats = EE_Registry::instance()->load_model('Datetime')->get_formats_for('DTT_EVT_start');
        $date_in_site_format = $datetime->format(implode(' ', $formats));
        $this->assertEquals(
            mysql_to_rfc3339($current_time_mysql),
            ModelDataTranslator::prepareFieldValueForJson(
                $field_obj,
                $date_in_site_format,
                '4.8.36'
            )
        );
    }



    /**
     * If you pass in an empty string, it's the same as using the current time in the field's timezone
     * @group 10869
     */
    public function testPrepareFieldValueForJsonPassingInEmptyString(){
        $field_obj = EE_Registry::instance()->load_model('Datetime')->field_settings_for('DTT_EVT_start');
        $field_obj->set_timezone('America/Vancouver');
        update_option('timezone_string', 'America/Vancouver');
        $this->assertDateWithinOneMinute(
            mysql_to_rfc3339(current_time('mysql'), false),
            ModelDataTranslator::prepareFieldValueForJson(
                $field_obj,
                '',
                '4.8.36'
            ),
            'Y-m-d\TH:i:s'
        );
    }

}

// Location: tests/testcases/core/libraries/rest_api/ModelDataTranslator_Test.php
