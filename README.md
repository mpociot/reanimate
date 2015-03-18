<img src="http://www.titaniumcontrols.com/git/reanimate.png" style="width: 100%" alt="Reanimate" />

<a href="https://packagist.org/packages/mpociot/reanimation">
    <img src="http://img.shields.io/github/tag/mpociot/reanimation.svg?style=flat" style="vertical-align: text-top">
</a>
<a href="https://packagist.org/packages/mpociot/reanimation">
    <img src="http://img.shields.io/packagist/dt/mpociot/reanimation.svg?style=flat" style="vertical-align: text-top">
</a>

Restoring models in laravel is easy. Simply call `restore` on the soft-deleted model and you're good to go. But what if you want to implement a simple `undo` mechanism in your application?

You need to take care of restoring the model, redirecting back, showing a success/error message...

Wouldn't it be nice if this process could be simplified?

Reanimate is a laravel package that allows you to do just that. It simplifies undoing of soft-deletes for your application.


## Installation

Reanimation can be installed via [composer](http://getcomposer.org/doc/00-intro.md), the details are on [packagist, here.](https://packagist.org/packages/mpociot/reanimation)

To download and use this package, add the following to the `require` section of your projects composer.json file:

```php
"mpociot/reanimation": "1.*",
```

Run composer update to download the package

```
php composer.phar update
```

That's it. Take a look at the implementation guide, to get started.

## Docs

* [What Reanimate does](#about)
* [Implementation](#intro)
* [Contributing](#contributing)

<a name="about"></a>
## What Reanimate does
A simplified `delete`method on your controller might look like this:

```php
public function delete( User $user )
{
	$user->delete();
	return Redirect::route( "userIndex" )->with( "message", "userDeleted" );
}
```

So how would you undo that delete call? You need to create a route, pass that object id, resolve the object, restore it if it exists and finally redirect back.

With Reanimate, this becomes:


```php
public function delete( User $user )
{
	$user->delete();
	return Redirect::route( "userIndex" )->with( "message", "userDeleted" )->with( $this->undoFlash( $user ) );
}
```

`undoFlash` passes an array to your session with the following data:

```php
"undo" => [
	"route"  => "userUndo",
	"params" => [ "DELETED_MODEL_ID" ],
	"lang"   => "user.undo.message"
]
```

So if your session has an `undo` array, you know that you should present your users a possibility to undo the deletion.

Just make sure that the undo route, points to the `undoDelete` method, available through the Reanimate Trait.

This method will then restore the model with the given ID and will redirect the user to a given index route.

<a name="intro"></a>
## Implementation & configuration

Every controller in your application, that currently takes care of deleting a model and now should benefit from Reanimate, needs to use the `ReanimateModels` Traits.

For example

```php
namespace App\Http\Controllers;

class UserController extends Controller {
    use \Mpociot\Reanimate\ReanimateModels;
}
```

### Deletes
When you redirect your users after a successful `delete` call, simply append the `->with( $this->undoFlash( $deletedModel ) );` data to your redirect.

undoFlash will automatically do two things for you:

**Auto generate an undo route:**

When no custom undo route is specified, Reanimate generates a named route by using the model name + "Undo".

Example model names and undo routes:
> User -> userUndo
> 
> Category -> categoryUndo
>
> FileEntry -> fileEntryUndo

**Auto generate an undo language key:**

By default, a `lang` key can be used within your views, for the undo action.
This key is a lowercase representation of your model name + `.undo.message`

Example model names and lang keys:
> User -> user.undo.message
> 
> Category -> category.undo.message
>
> FileEntry -> fileentry.undo.message


### Restores

The easiest way to restore your models, is to add a named route to your `routes.php` that matches the generated undo route name.

This route needs to point to the `undoDelete` method of your controller.

This method takes care of restoring the model with the given primary key and returns a redirect to an auto generated index route.

**Auto generated model name:**

Since this method only receives the ID of your model, but not the model itself, Reanimate tries to guess the correct model name for you.

It's done by using the singular version of your Controller's name.

Example controller names and the matching model names:
> UserController -> User
> 
> CategoriesController -> Category
>
> FileEntryController -> FileEntry

**Auto generated index route:**

When no custom index route is specified, Reanimate generates a named route by using the model name + "Index".

Example model names and undo routes:
> User -> userIndex
> 
> Category -> categoryIndex
>
> FileEntry -> fileEntryIndex

The redirect will also contain a flash data named `message` that contains either:

`modelname.undo.restored` on success or `modelname.undo.invalid` when no model with the given ID could be found.


### Customization

Don't like the auto generated routes? Don't like the auto generated model names? No problem!

Simply override them in your controller class like this:


```php
namespace App\Http\Controllers;

class UserController extends Controller {
    use \Mpociot\Reanimate\ReanimateModels;
    
    protected $undoDeleteModel = 'This\Is\My\Custom\Model';
    
    protected $indexRoute = 'home';
    
    protected $undoRoute = 'ohNooooo';
    
}
```

You can also write your own `undoDelete` method, if you want some more control:


```php
namespace App\Http\Controllers;

class UserController extends Controller {
    use \Mpociot\Reanimate\ReanimateModels;
    
    public function myCustomUndoMethod( $primaryKey )
    {
    	return $this->restoreModel( $primaryKey , new User(), "myCustomIndexRoute" );
    }
    
}
```


<a name="contributing"></a>
## Contributing

Contributions are encouraged and welcome; to keep things organised, all bugs and requests should be
opened in the github issues tab for the main project, at [mpociot/reanimate/issues](https://github.com/mpociot/reanimate/issues)

All pull requests should be made to the `develop` branch, so they can be tested before being merged into the master branch.