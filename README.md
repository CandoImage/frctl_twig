FrctlTwigExtension
===========================

The FrctlTwigExtension component allows you set seamlessly integrate your [Fractal twig templates].


Installation
------------

To install the latest stable version of this component, open a console and execute the following command:
```
$ composer require candoimage/frctl_twig
```

Usage
-----

The first step is to [register the extension](https://symfony.com/doc/2.1/cookbook/templating/twig_extension.html#register-an-extension-as-a-service) in the twig environment

```yml
services:
    Frctl\Twig\Extension\FrctlTwigExtension:
        tags:
            - { name: twig.extension }
```

or manually in your code

```php
/* @var $twig Twig_Environment */
$twig->addExtension(new Frctl\Twig\Extension\FrctlTwigExtension());
```

Then you need to [configure the new include namespace](https://symfony.com/doc/current/templating/namespaced_paths.html) the fractal templates:
```yml
twig:
  paths:
    '%kernel.project_dir%/../frontend/src/components': FrctlTwig
```

or manually in your code

```php
$loader->addPath(dirname(__DIR__) . '/../frontend/src/components', 'FrctlTwig');
```


Once registered, you can use the new `render` tag [seamlessly](https://github.com/frctl/twig#render) in your twig templates:
```twig
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>title</title>
  </head>
  <body>
    {% render "@component" with {some: 'values'} %}
  </body>
</html>
```

Advanced examples:
```twig
{# @component will have access to the variables from the current context and the additional ones provided #}
{% render'@component' with {'foo': 'bar'} %}

{# only the foo variable will be accessible #}
{% render'@component' with {'foo': 'bar'} only %}

{# @component will have access to the variables from the current context and the additional ones provided and the ones provided in the fractal yml config #}
{% render'@component' with {'foo': 'bar'} merge frctl context %}

{# only the foo variable and the ones provided in the fractal yml config will be accessible #}
{% render'@component' with {'foo': 'bar'} only merge frctl context %}
```

The variable `variant` contains the selected variant.

License
-------

This component is under the MIT license. See the complete license in the [LICENSE] file.


Reporting an issue or a feature request
---------------------------------------

Issues and feature requests are tracked in the [Github issue tracker].


Author Information
------------------

[Peter Philipp @ Cando Image].

If you find this component useful, please add a ★ in the [GitHub repository page].

[Fractal twig templates]: https://github.com/frctl/twig
[LICENSE]: LICENSE
[Github issue tracker]: https://github.com/CandoImage/frctl_twig/issues
[Peter Philipp @ Cando Image]: https://www.cando-image.com
[GitHub repository page]: https://github.com/CandoImage/frctl_twig
[Fractal twig templates]: https://github.com/frctl/twig