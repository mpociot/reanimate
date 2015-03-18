<?php
namespace App\Http\Controllers;


use Mpociot\Reanimate\ReanimateModels;

class UserController {

    use ReanimateModels;

    public function deleteModel( $modelID, $customRoute = "" )
    {
        $user = \User::find( $modelID );
        $user->delete();

        return $this->undoFlash($user, $customRoute );
    }

}