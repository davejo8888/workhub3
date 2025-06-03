<?php
// This file: workhub/includes/footer.php
?>
</main> <footer class="bg-white border-t mt-12 py-6">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center text-gray-500 text-sm">
        &copy; <?php echo date('Y'); ?> MyWorkHub. Created by Dr. Ahmed AL-sadi. All rights reserved.
        </div>
</footer>

<?php
// You can output page-specific JavaScript variables or include scripts here
if (isset($pageScripts) && is_array($pageScripts)) {
    foreach ($pageScripts as $script) {
        echo '<script src="' . htmlspecialchars($script) . '"></script>' . "\n";
    }
}
?>
</body>
</html>