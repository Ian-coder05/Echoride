<?php
session_start();
session_unset();
session_destroy();

// Clear localStorage through JavaScript
echo "<script>
    localStorage.removeItem('isLoggedIn');
    localStorage.removeItem('userName');
    window.location.href = 'index.html';
</script>";
exit;
?> 