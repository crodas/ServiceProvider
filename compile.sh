#!/bin/bash

php vendor/crodas/simple-view-engine/cli.php compile -N ServiceProvider\\Template  $(pwd)/lib/ServiceProvider/Template $(pwd)/lib/ServiceProvider/Template/Templates.php
