<?php
require_once 'PHPUnit/Framework.php';

require_once '../DatabaseRow.php';
require_once '../DatabaseQuery.php';

/**
 * Test class for DatabaseRow.
 * Generated by PHPUnit on 2008-05-06 at 09:23:23.
 */
class DatabaseCompanyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var dsn
     */
    //protected $dsn = 'pgsql:host=localhost port=5432 dbname=testdb user=dbrow password=dbrow';
    protected $dsn = 'sqlite::memory:';
    //protected $dsn = 'sqlite:companytest.sq3';

    /**
     * @var db
     */
    protected $db;

    /**
     * @var    DatabaseRow
     * @access protected
     */
    protected $object;
    protected $query;
    protected $table;
    protected $params;

    /**
     * @var mock data
     * @access protected
     */
    protected $mockData = array(
        //'id'  => 0,
        'name'    => 'admin',
        'email'     => 'admin@abc.com'
    );


    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->mockData = (object)$this->mockData;

        try {
            $this->db = new PDO($this->dsn);
        }
        catch (PDOException $e) {
            $this->fail('Connect database error! ' . $e->getMessage());
        }


        $this->params->db = $this->db;
        $this->params->schema = new Schema(array(
            'Company' => array(
                '@primaryKey' => 'id',
                '@unique' => 'name',

                'id' => array(
                    'type' => 'int',
                    'default' => true
                ),
                'name' => array(
                    'type' => 'string',
                    'default' => false,
                ),
                'email' => array(
                    'type' => 'string'
                )
            )
        ));
        $this->table = 'Company';
        $this->params->table = $this->table;

        $this->object = new DatabaseRow($this->params);
        /* the other way is:
        $this->object = new DatabaseRow(array(
            'db'        => $this->db,
            'schema'    => new Schema('Schema_TestSchema.js'),
            'table'     => $this->table
        ));
        */
        $this->query = new DatabaseQuery( $this->params );


        $this->db->query('CREATE TABLE "'.$this->table.'" (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name varchar(40) UNIQUE,
            email varchar(255)
        );');

        $this->db->query('INSERT INTO "'.$this->table.'" (name, email)
                VALUES (\'rock\', \'rock@abc.com\');');
        //to let next id > 1

        //$this->db->exec('DELETE FROM "'.$this->table.'";');
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }

    /**
     * return.
     */
    public function refresh(&$row) {
        $schema = $row->schema();
        $unique = $schema->attribute('unique');

        $results = $this->query
                    ->from($this->table)
                    ->where(array($unique => $row->$unique))
                    ->select();

        //echo $this->query->getLastQueryString(), "\n";
        if ($results) {
            //print_r($results[0]);
            return $results[0];
        }
        else
            return false;
    }

    /**
     * no return.
     */
    public function assignBack(&$row) {
        $schema = $row->schema();
        $unique = $schema->attribute('unique');

        $results = $this->query
                    ->from($this->table)
                    ->where(array($unique => $row->$unique))
                    ->select();

        //echo $this->query->getLastQueryString(), "\n";
        if ($results) {
            //print_r($results[0]);
            $row->assign($results[0]);
        }
    }

    /**
     * @test
     */
    public function crud() {
        $data = $this->mockData;
        $data->name = 'admin1';

        /**
         * Insert an AUTOINCREMENT record.
         * Way 1:
         * $callback should return result back to let insert() assign to instance.
         */
        $this->object->assign($data);
        $this->object->insert(array($this, 'refresh'));
        //refresh: to get the new id.

        $this->assertTrue( $this->object['id'] > 1);
        $this->assertEquals($data->name, $this->object['name']);

        $id = $this->object->id;

        $newEmail = 'new_rock@abc.com';
        $this->object->email = $newEmail;

        $this->object->update();
        
        $result = $this->object->get();

        $this->assertEquals($id, $result->id);
        $this->assertEquals($id, $this->object->id);

        $this->assertEquals($data->name, $result->name);
        $this->assertEquals($data->name, $this->object->name);

        $this->assertEquals($newEmail, $result->email);
        $this->assertEquals($newEmail, $this->object->email);

        $this->assertTrue( $this->object->delete() );

        $result = $this->object->get($id);
        $this->assertTrue(empty($result));
    }

    /**
     * @test
     */
    public function crud2() {
        $data = $this->mockData;
        $data->name = 'admin2';

        /**
         * Insert an AUTOINCREMENT record.
         * Way 2:
         * $callback got the refreence to instance, and assigns data to instance.
         */
        $this->object->assign($data);
        $this->object->insert(array($this, 'assignBack'));
        //refresh: to get the new id.

        $this->assertTrue( $this->object['id'] > 1);
        $this->assertEquals($data->name, $this->object['name']);

        $id = $this->object->id;

        $newEmail = 'new_rock@abc.com';
        $this->object->email = $newEmail;

        $this->object->update();

        $result = $this->object->get();

        $this->assertEquals($id, $result->id);
        $this->assertEquals($id, $this->object->id);

        $this->assertEquals($data->name, $result->name);
        $this->assertEquals($data->name, $this->object->name);

        $this->assertEquals($newEmail, $result->email);
        $this->assertEquals($newEmail, $this->object->email);

        $this->assertTrue( $this->object->delete() );

        $result = $this->object->get($id);
        $this->assertTrue(empty($result));
    }

    /**
     * @test
     */
    public function crud3() {
        $newEmail = 'new_rock@abc.com';

        $results = $this->query
                    ->from($this->table)
                    ->where(array('name' => 'rock'))
                    ->select();
        //echo $this->query->getLastQueryString(), "\n";
        $this->assertFalse( empty($results) );
        $this->assertEquals( 1, count($results) );

        $rock = $this->object->factory( $results[0] );
        $id = $rock['id'];

        $rock['email'] = $newEmail;

        $rock->update();

        $result = $rock->get();

        $this->assertEquals($rock->id, $result->id);

        $this->assertEquals($rock->name, $result->name);

        $this->assertEquals($rock->email, $result->email);

        $this->assertTrue( $rock->delete() );

        $result = $this->object->get($id);
        $this->assertTrue(empty($result));
    }

}

// Run this test if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) {
    require_once 'PHPUnit/TextUI/TestRunner.php';

    $suite  = new PHPUnit_Framework_TestSuite('DatabaseCompanyTest');
    $result = PHPUnit_TextUI_TestRunner::run($suite);
}
?>
