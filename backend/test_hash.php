<?php
// Esto nos dirá qué hash genera TU servidor para 'admin123'
echo password_hash("admin123", PASSWORD_BCRYPT);
?>