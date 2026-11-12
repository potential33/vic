<?php
declare(strict_types=1);
require __DIR__ . '/db.php';

db();
echo "MySQL InnoDB enforces RESTRICT when a foreign key has no ON DELETE action.\n";
echo "The class_arms and sessions constraints are defined in schema.sql.\n";
