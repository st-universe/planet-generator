STU3 Planet Surface Generator
=============================

Nutzung
-----

Siehe `PlanetGenerator` Klasse.

Beispiel
--------

- Abhängigkeiten installieren `composer install`
- Feld-Generator laufen lassen
    ```php
    php example/assets/generator/field_generator/generator.php
    ````
- PHP Webserver starten `php -S localhost:1338 -t example`

`http://localhost:1338` im Webbrowser öffnen. Mittels des `type` Parameters
kann die Oberfläche eines beliebigen Planetentypen generiert werden.
