<?php
echo '<pre>';
echo 'admin123:   ' . password_hash('admin123', PASSWORD_BCRYPT) . "\n";
echo 'cliente123: ' . password_hash('cliente123', PASSWORD_BCRYPT) . "\n";
echo '</pre>';
// ELIMINA QUESTO FILE DOPO L'USO