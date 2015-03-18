<?php

use Mockery as m;
use Illuminate\Database\Capsule\Manager as DB;

class ReanimationTest extends PHPUnit_Framework_TestCase
{

    use \Mpociot\Reanimate\ReanimateModels;

    public static function setUpBeforeClass()
    {
        with( new UserMigrator() )->up();
    }

    public function setUp()
    {
        with( new UserSeeder() )->run();

        $app = new ApplicationStub;
        \Illuminate\Support\Facades\Facade::setFacadeApplication($app);

        if( !function_exists('trans') )
        {
            function trans( $key ){ return $key; }
        }
    }

    public function testSeededDataIsInitialized()
    {
        $this->assertEquals( 4, User::count() );
    }

    public function testTraitFunctionsExist()
    {
        $this->assertTrue( method_exists( $this, "undoFlash" ) );
        $this->assertTrue( method_exists( $this, "undoDelete" ) );
    }

    public function testUndoDefaultFlashData()
    {
        // Undo a delete without a custom undo route
        $uc            = new \App\Http\Controllers\UserController();
        $undoFlashData = $uc->deleteModel( 1 );

        $this->assertTrue( is_array( $undoFlashData ) );
        $this->assertArrayHasKey( "undo", $undoFlashData );
        $this->assertArrayHasKey( "route", $undoFlashData[ "undo" ] );
        $this->assertArrayHasKey( "params", $undoFlashData[ "undo" ] );
        $this->assertTrue( is_array( $undoFlashData[ "undo" ][ "params" ] ) );
        $this->assertArrayHasKey( "lang", $undoFlashData[ "undo" ] );

        $this->assertEquals( "userUndo", $undoFlashData[ "undo" ][ "route" ] );
        $this->assertEquals( 1, $undoFlashData[ "undo" ][ "params" ][ 0 ] );
        $this->assertEquals( "user.undo.message", $undoFlashData[ "undo" ][ "lang" ] );
    }

    public function testUndoCustomFlashData()
    {
        // Undo a delete without a custom undo route
        $uc            = new \App\Http\Controllers\UserController();
        $undoFlashData = $uc->deleteModel( 2, "myCustomUndoRoute" );

        $this->assertTrue( is_array( $undoFlashData ) );
        $this->assertArrayHasKey( "undo", $undoFlashData );
        $this->assertArrayHasKey( "route", $undoFlashData[ "undo" ] );
        $this->assertArrayHasKey( "params", $undoFlashData[ "undo" ] );
        $this->assertTrue( is_array( $undoFlashData[ "undo" ][ "params" ] ) );
        $this->assertArrayHasKey( "lang", $undoFlashData[ "undo" ] );

        $this->assertEquals( "myCustomUndoRoute", $undoFlashData[ "undo" ][ "route" ] );
        $this->assertEquals( 2, $undoFlashData[ "undo" ][ "params" ][ 0 ] );
        $this->assertEquals( "user.undo.message", $undoFlashData[ "undo" ][ "lang" ] );
    }

    public function testUndoControllerRouteFlashData()
    {
        // Undo a delete without a custom undo route
        $uc            = new \App\Http\Controllers\UserController();
        $uc->undoRoute = "restoreRoute";
        $undoFlashData = $uc->deleteModel( 3 );

        $this->assertTrue( is_array( $undoFlashData ) );
        $this->assertArrayHasKey( "undo", $undoFlashData );
        $this->assertArrayHasKey( "route", $undoFlashData[ "undo" ] );
        $this->assertArrayHasKey( "params", $undoFlashData[ "undo" ] );
        $this->assertTrue( is_array( $undoFlashData[ "undo" ][ "params" ] ) );
        $this->assertArrayHasKey( "lang", $undoFlashData[ "undo" ] );

        $this->assertEquals( "restoreRoute", $undoFlashData[ "undo" ][ "route" ] );
        $this->assertEquals( 3, $undoFlashData[ "undo" ][ "params" ][ 0 ] );
        $this->assertEquals( "user.undo.message", $undoFlashData[ "undo" ][ "lang" ] );
    }

    public function testRestoreSuccess()
    {

        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "userIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.restored"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );


        $uc       = new \App\Http\Controllers\UserController();
        $redirect = $uc->undoDelete( 1 );
    }

    public function testRestoreFailing()
    {

        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "userIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.invalid"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );


        $uc       = new \App\Http\Controllers\UserController();
        $redirect = $uc->undoDelete( 10000 );
    }

    public function testRestoreCustomIndexRoute()
    {

        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "customIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.invalid"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );


        $uc       = new \App\Http\Controllers\UserController();
        $uc->indexRoute = "customIndex";
        $redirect = $uc->undoDelete( 10000 );
    }

    public function testCustomRestoreValid()
    {

        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "userIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.restored"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );


        $uc       = new \App\Http\Controllers\UserController();
        $redirect = $uc->restoreModel( 1, new User() );
    }

    public function testCustomRestoreInvalid()
    {

        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "customIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.invalid"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );


        $uc       = new \App\Http\Controllers\UserController();
        $uc->indexRoute = "customIndex";
        $redirect = $uc->restoreModel( 10000, new User() );
    }

    public function testCustomRestoreRoute()
    {

        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "customIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.invalid"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );


        $uc       = new \App\Http\Controllers\UserController();
        $redirect = $uc->restoreModel( 10000, new User(), "customIndex" );
    }

    public function testNoMatchingModuleUndoThrowsException()
    {
        $controller       = new \App\Http\Controllers\NoMatchingModelController();
        $this->setExpectedException('Symfony\Component\Debug\Exception\ClassNotFoundException');
        $controller->undoDelete( 1 );
    }

    public function testNoMatchingModuleUndoWithCustomModel()
    {
        $mockRedirect = Mockery::mock('\Illuminate\Http\Redirector');
        $mockRedirect->shouldReceive( 'route' )->with( "userIndex" )->andReturnSelf();
        $mockRedirect->shouldReceive( 'with' )->withArgs([
            "message", "user.undo.restored"
        ])->andReturnSelf();
        \Illuminate\Support\Facades\Redirect::swap( $mockRedirect );

        $controller       = new \App\Http\Controllers\NoMatchingModelController();
        $controller->undoDeleteModel = get_class( new User() );
        $controller->undoDelete( 1 );
    }

}

class ApplicationStub implements ArrayAccess {
    protected $attributes = array();
    public function setAttributes($attributes) { $this->attributes = $attributes; }
    public function instance($key, $instance) { $this->attributes[$key] = $instance; }
    public function offsetExists($offset) { return isset($this->attributes[$offset]); }
    public function offsetGet($key) { return $this->attributes[$key]; }
    public function offsetSet($key, $value) { $this->attributes[$key] = $value; }
    public function offsetUnset($key) { unset($this->attributes[$key]); }
}