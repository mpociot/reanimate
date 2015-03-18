<?php
namespace Mpociot\Reanimate;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\Debug\Exception\ClassNotFoundException;

/**
 * Class Reanimate
 *
 * Automagically manages soft-delete restores.
 * All you need to do is use this trait inside your controller and (optionally)
 * set the model to use for restoring
 *
 * When deleting the model append a new flash message to your redirect using the undoFlash method.
 *
 */
trait ReanimateModels
{

    /**
     * Returns the Model for the undo operation
     * If it doesn't exist it tries to generate the class name by using the calling class name
     * stripping of "Controller" and applying str_singular
     * So a "CategoriesController" would look for a model called "Category"
     *
     * If that class also doesn't exist it throws a ClassNotFoundException
     *
     * @return \Eloquent
     * @throws ClassNotFoundException
     */
    private function getUndoModel()
    {
        if ( isset( $this->undoDeleteModel ) )
        {
            return new $this->undoDeleteModel;
        } else
        {
            // Try to guess the model name
            $self          = new \ReflectionClass( $this );
            $replacedClass = strrev( preg_replace( strrev( "/Controller/" ), "", strrev( $self->getShortName() ), 1 ) );
            $modelClass    = str_singular( $replacedClass );

            if ( class_exists( $modelClass ) )
            {
                return new $modelClass;
            } else
            {
                throw new ClassNotFoundException( "The model class could not be generated. Please define the 'undoDeleteModel' property in your Controller.", new \ErrorException() );
            }
        }
    }

    /**
     * Undo delete a model with a given primary key
     *
     * @param $primary_key int
     *
     * @param $obj
     * @return \Illuminate\Http\RedirectResponse
     */
    public function restoreModel( $primary_key, $obj, $customIndexRoute = "" )
    {
        $model = $obj->withTrashed()->where( $obj->getKeyName(), "=", (int)$primary_key )->first();

        $modelClass = new \ReflectionClass( get_class( $obj ) );
        $modelName  = lcfirst( $modelClass->getShortName() );

        // Try to auto-generate the indexRoute if it doesn't exist
        if ( $customIndexRoute != "" )
        {
            $indexRoute = $customIndexRoute;
        } elseif( isset( $this->indexRoute ) )
        {
            $indexRoute = $this->indexRoute;
        } else
        {
            // Autogenerate index route
            $indexRoute = $modelName . "Index";
        }

        if ( !is_null( $model ) )
        {
            $model->restore();
            return Redirect::route( $indexRoute )->with( "message", $modelName . ".undo.restored" );
        } else
        {
            return Redirect::route( $indexRoute )->with( "message", $modelName . ".undo.invalid" );
        }
    }

    /**
     * This is a ready-to-use route to restore models
     *
     * @param $primary_key
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function undoDelete( $primary_key )
    {
        return $this->restoreModel( $primary_key, $this->getUndoModel() );
    }


    /**
     * Returns the undo flash data to append to your delete
     * redirects
     *
     * @param \Eloquent $model the deleted model
     *
     * @param string $customUndoRoute
     * @return array
     */
    protected function undoFlash( $model, $customUndoRoute = "" )
    {
        $modelClass = new \ReflectionClass( get_class( $model ) );

        // Try to auto-generate the undoRoute if it doesn't exist
        if ( $customUndoRoute != "" )
        {
            $undoRoute = $customUndoRoute;
        } elseif ( !isset( $this->undoRoute ) )
        {
            $undoRoute = lcfirst( $modelClass->getShortName() ) . "Undo";
        } else
        {
            $undoRoute = $this->undoRoute;
        }

        return [
            "undo" => [
                "route"  => $undoRoute,
                "params" => [ $model->getKey() ],
                "lang"   => strtolower( $modelClass->getShortName() ) . ".undo.message"
            ]
        ];
    }

}