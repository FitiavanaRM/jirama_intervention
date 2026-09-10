<?php

declare(strict_types=1);

if (!defined('JIRAMA_LAYOUT')) {
    http_response_code(403);
    exit('Accès direct interdit.');
}
?>
        <footer>
            <p>&copy; <?= date('Y') ?> JIRAMA Toliara</p>
        </footer>
    </div>
</body>
</html>