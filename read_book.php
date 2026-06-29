<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: user_login.php");
    exit();
}
require_once 'db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reading Book - LibraryMS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<?php
if (!isset($_GET['id'])) {
    die("Invalid Book ID.");
}

$book_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

// Verify the user actually borrowed this book right now
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE book_id = ? AND borrower_id = ? AND status = 'borrowed'");
$stmt->execute([$book_id, $user_id]);
$transaction = $stmt->fetch();

if (!$transaction) {
    echo "<div class='container mt-5 py-5 text-center'>";
    echo "<h2 class='text-danger mb-4'><i class='bi bi-x-circle fs-1 d-block mb-3'></i>Access Denied</h2>";
    echo "<p class='text-muted fs-5'>You have not borrowed this book or you have already returned it. You must borrow it first to read it.</p>";
    echo "<a href='user_dashboard.php' class='btn btn-primary mt-3'>Go to Dashboard</a>";
    echo "</div>";
    require_once 'footer.php';
    exit();
}

// Fetch Book details
$book_stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
$book_stmt->execute([$book_id]);
$book = $book_stmt->fetch();

if (!$book) {
    die("Book not found.");
}
?>

<!-- Import Premium Font -->
<link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&family=Inter:wght@400;600&display=swap" rel="stylesheet">

<style>
    /* Premium E-reader Styles */
    :root {
        --reader-bg: #f9f7f1; /* Ivory/Sepia */
        --reader-text: #2b2b2b;
        --reader-accent: #8b5cf6;
    }
    
    body.reading-mode {
        background-color: var(--reader-bg) !important;
        color: var(--reader-text) !important;
        transition: background-color 0.4s ease, color 0.4s ease;
    }
    
    body.reading-mode.dark {
        --reader-bg: #1a1a1a;
        --reader-text: #e5e5e5;
    }

    .reader-navbar {
        background-color: var(--reader-bg);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 15px 0;
        transition: all 0.4s ease;
    }
    body.reading-mode.dark .reader-navbar {
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .book-title-nav {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 1rem;
        letter-spacing: 0.5px;
    }
    
    .reader-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 60px 20px;
    }

    .reader-header {
        text-align: center;
        margin-bottom: 60px;
    }

    .reader-header img {
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        border-radius: 4px;
        max-width: 220px;
        margin-bottom: 30px;
    }

    .reader-title {
        font-family: 'Lora', serif;
        font-weight: 600;
        font-size: 2.5rem;
        margin-bottom: 10px;
        color: var(--reader-text);
    }

    .reader-author {
        font-family: 'Inter', sans-serif;
        font-weight: 400;
        font-size: 1.1rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 2px;
    }
    
    body.reading-mode.dark .reader-author {
        color: #9ca3af;
    }

    .reader-content {
        font-family: 'Lora', serif;
        font-size: 1.25rem;
        line-height: 2.2;
        text-align: justify;
    }

    /* Drop cap for first paragraph */
    .reader-content > div > p:first-of-type::first-letter,
    .reader-content > p:first-of-type::first-letter {
        font-size: 4rem;
        font-weight: 600;
        line-height: 1;
        float: left;
        margin-right: 8px;
        margin-top: 5px;
        color: var(--reader-accent);
    }
    
    .btn-reader-tool {
        background: transparent;
        border: none;
        color: var(--reader-text);
        font-size: 1.2rem;
        padding: 8px 12px;
        border-radius: 8px;
        transition: background 0.2s;
    }
    .btn-reader-tool:hover {
        background: rgba(0,0,0,0.05);
    }
    body.reading-mode.dark .btn-reader-tool:hover {
        background: rgba(255,255,255,0.1);
    }

    footer.footer, footer.public-footer {
        display: none !important;
    }
</style>
</head>
<body class="reading-mode">

<div class="reading-wrapper">
    <!-- Navbar -->
    <div class="reader-navbar sticky-top">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="user_dashboard.php" class="btn-reader-tool text-decoration-none d-flex align-items-center">
                <i class="bi bi-arrow-left me-2"></i> <span style="font-size: 0.95rem; font-family: 'Inter', sans-serif;">Library</span>
            </a>
            
            <div class="book-title-nav text-truncate mx-3" style="max-width: 50%;">
                <?php echo htmlspecialchars($book['title']); ?>
            </div>
            
            <div class="reader-tools">
                <button class="btn-reader-tool" id="btn-zoom-out" title="Decrease Font"><i class="bi bi-zoom-out"></i></button>
                <button class="btn-reader-tool" id="btn-zoom-in" title="Increase Font"><i class="bi bi-zoom-in"></i></button>
                <button class="btn-reader-tool" id="btn-theme-toggle" title="Toggle Dark/Light Mode"><i class="bi bi-moon-stars"></i></button>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="reader-container">
        <div class="reader-header">
            <?php 
                $cover = !empty($book['cover_url']) ? $book['cover_url'] : 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop';
            ?>
            <img src="<?php echo htmlspecialchars($cover); ?>" onerror="this.onerror=null; this.src='https://images.unsplash.com/photo-1544947950-fa07a98d237f?q=80&w=600&auto=format&fit=crop';" alt="Cover">
            <h1 class="reader-title"><?php echo htmlspecialchars($book['title']); ?></h1>
            <div class="reader-author"><?php echo htmlspecialchars($book['author']); ?></div>
            
            <?php if(!empty($book['description'])): ?>
                <div class="mt-5 text-center fst-italic" style="font-family: 'Lora', serif; font-size: 1.1rem; opacity: 0.8; max-width: 600px; margin: 0 auto;">
                    "<?php echo htmlspecialchars($book['description']); ?>"
                </div>
            <?php endif; ?>
            
            <hr style="margin: 40px auto; width: 50px; border-top: 2px solid var(--reader-accent); opacity: 1;">
        </div>

        <div class="reader-content" id="reader-text">
            <?php if(!empty($book['book_content'])): ?>
                <div>
                    <?php 
                        // Process the content to wrap in paragraphs for proper drop-caps
                        $content = trim($book['book_content']);
                        $paragraphs = explode("\n", $content);
                        foreach($paragraphs as $p) {
                            $p = trim($p);
                            if (!empty($p)) {
                                echo "<p>" . htmlspecialchars($p) . "</p>";
                            }
                        }
                    ?>
                </div>
            <?php else: ?>
                <p>It was a dark and stormy night when the story began. The wind howled through the empty streets, carrying with it the whispers of forgotten tales. For centuries, this knowledge had been hidden away in dusty archives, accessible only to a select few. But today, everything changes.</p>
                <p>The pages of this digital tome flutter not with the breeze, but with the rapid progression of technology. You are holding in your hands the culmination of human imagination, curated carefully for your reading pleasure. As you delve deeper into these words, you will find yourself transported to distant lands, meeting characters who will become your closest companions.</p>
                <p>This is merely a placeholder chapter designed for the LibraryMS system, a testament to the fact that while we manage the inventory, it is the reader who breathes life into the stories. The true content of the book lies within the physical pages stored safely in our shelves, waiting for you to turn them.</p>
                <p>Until then, let your imagination run wild. Every great journey begins with a single word, and every great mind is shaped by the books they consume. Keep reading, keep learning, and welcome to your new literary adventure.</p>
            <?php endif; ?>
            
            <div class="text-center mt-5 pt-5 pb-5">
                <i class="bi bi-three-dots fs-1" style="color: var(--reader-accent);"></i>
                <p class="mt-3" style="font-family: 'Inter', sans-serif; font-size: 0.9rem; letter-spacing: 1px; text-transform: uppercase; opacity: 0.6;">End of Book</p>
            </div>
        </div>
    </div>
</div>

<script>
    // Initialize Reader Mode
    document.body.classList.add('reading-mode');
    
    // Theme Toggle
    const btnTheme = document.getElementById('btn-theme-toggle');
    const iconTheme = btnTheme.querySelector('i');
    
    // Check saved theme
    if (localStorage.getItem('reader-theme') === 'dark') {
        document.body.classList.add('dark');
        iconTheme.classList.replace('bi-moon-stars', 'bi-sun');
    }
    
    btnTheme.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        if (document.body.classList.contains('dark')) {
            localStorage.setItem('reader-theme', 'dark');
            iconTheme.classList.replace('bi-moon-stars', 'bi-sun');
        } else {
            localStorage.setItem('reader-theme', 'light');
            iconTheme.classList.replace('bi-sun', 'bi-moon-stars');
        }
    });

    // Font Size Controls
    const content = document.getElementById('reader-text');
    let currentSize = 1.25; // rem
    
    document.getElementById('btn-zoom-in').addEventListener('click', () => {
        if (currentSize < 2.0) {
            currentSize += 0.1;
            content.style.fontSize = currentSize + 'rem';
        }
    });
    
    document.getElementById('btn-zoom-out').addEventListener('click', () => {
        if (currentSize > 0.9) {
            currentSize -= 0.1;
            content.style.fontSize = currentSize + 'rem';
        }
    });
</script>

</body>
</html>
