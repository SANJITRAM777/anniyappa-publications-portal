<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$post_id = (int)($_GET['id'] ?? 0);

// Fetch post details
$stmt = $pdo->prepare("
    SELECT p.*, p.category AS category_name, p.author AS author_name, '' AS author_bio
    FROM blog_posts p
    WHERE p.id = ? AND p.status = 'Published'
");
$stmt->execute([$post_id]);
$post = $stmt->fetch();

if (!$post) {
    header("Location: /blog.php");
    exit;
}

$page_title = sanitize($post['title']) . " - Anniyappa Publications Blog";

// Process new comment
$comment_success = '';
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    if (!is_logged_in()) {
        $_SESSION['login_redirect'] = "/blog_details.php?id=$post_id";
        header("Location: /login.php");
        exit;
    }
    
    $comment_text = trim($_POST['comment_text'] ?? '');
    
    if (empty($comment_text)) {
        $comment_error = "Comment text cannot be empty.";
    } else {
        try {
            $userStmt = $pdo->prepare("SELECT u.email, p.full_name FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE u.id = ?");
            $userStmt->execute([get_logged_in_user_id()]);
            $uinfo = $userStmt->fetch();
            $author_name = $uinfo ? $uinfo['full_name'] : 'User';
            $author_email = $uinfo ? $uinfo['email'] : '';

            $insComment = $pdo->prepare("INSERT INTO blog_comments (post_id, author_name, author_email, comment, status) VALUES (?, ?, ?, ?, 'Approved')");
            $insComment->execute([$post_id, $author_name, $author_email, $comment_text]);
            $comment_success = "Your comment has been posted successfully!";
        } catch (PDOException $e) {
            $comment_error = "Error adding comment: " . $e->getMessage();
        }
    }
}

// Fetch comments
$commentsStmt = $pdo->prepare("
    SELECT c.*, c.author_name AS full_name, 'default_avatar.png' AS profile_pic, c.comment AS comment_text 
    FROM blog_comments c
    WHERE c.post_id = ? AND c.status = 'Approved'
    ORDER BY c.created_at DESC
");
$commentsStmt->execute([$post_id]);
$comments = $commentsStmt->fetchAll();
?>

<?php include_once __DIR__ . '/includes/header.php'; ?>

<div class="container py-5">
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb text-muted">
      <li class="breadcrumb-item"><a href="/index.php" class="text-decoration-none">Home</a></li>
      <li class="breadcrumb-item"><a href="/blog.php" class="text-decoration-none">Blog</a></li>
      <li class="breadcrumb-item active" aria-current="page"><?php echo sanitize($post['title']); ?></li>
    </ol>
  </nav>

  <div class="row g-5">
    <div class="col-lg-8">
      <!-- Article Content Card -->
      <div class="card border-0 shadow-sm p-4 p-lg-5 mb-4" style="border-radius:20px;">
        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1.5 rounded-pill mb-3 align-self-start"><?php echo sanitize($post['category_name']); ?></span>
        <h1 class="font-title text-dark fw-bold mb-3" style="font-size:2.2rem;"><?php echo sanitize($post['title']); ?></h1>
        
        <div class="d-flex align-items-center flex-wrap gap-3 mb-4 text-muted small border-bottom pb-3">
          <span><i class="bi bi-person-circle text-primary me-1"></i><?php echo sanitize($post['author_name']); ?></span>
          <span><i class="bi bi-calendar3 text-primary me-1"></i><?php echo date('F d, Y', strtotime($post['created_at'])); ?></span>
          <span><i class="bi bi-chat-left-text text-primary me-1"></i><?php echo count($comments); ?> Comments</span>
        </div>

        <div class="text-dark fs-6" style="line-height:1.8;">
          <?php echo nl2br(sanitize($post['content'])); ?>
        </div>
      </div>

      <!-- Author Card -->
      <div class="card border-0 bg-light p-4 mb-5" style="border-radius:15px;">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4" style="width: 50px; height: 50px;">
            <?php echo substr($post['author_name'], 0, 1); ?>
          </div>
          <div>
            <h5 class="fw-bold mb-0 text-dark"><?php echo sanitize($post['author_name']); ?></h5>
            <small class="text-muted">Editorial Writer & Faculty Coordinator</small>
          </div>
        </div>
        <p class="text-muted small mb-0 mt-3"><?php echo sanitize($post['author_bio'] ?: 'Contributor at Anniyappa Publications.'); ?></p>
      </div>

      <!-- Comments Segment -->
      <div class="border-top pt-5">
        <h4 class="fw-bold text-dark mb-4"><i class="bi bi-chat-dots me-2 text-primary"></i>Comments (<?php echo count($comments); ?>)</h4>
        
        <?php if (!empty($comment_success)): ?>
          <?php echo get_alert($comment_success, 'success'); ?>
        <?php endif; ?>

        <?php if (!empty($comment_error)): ?>
          <?php echo get_alert($comment_error, 'danger'); ?>
        <?php endif; ?>

        <!-- Form for comments -->
        <form action="/blog_details.php?id=<?php echo $post_id; ?>" method="POST" class="mb-4 bg-light p-4 border rounded-3">
          <h5 class="fw-bold text-dark h6 mb-3">Leave a Reply</h5>
          <div class="mb-3">
            <textarea name="comment_text" rows="3" class="form-control bg-white" placeholder="Add your constructive comments here..." required></textarea>
          </div>
          <button type="submit" name="submit_comment" class="btn btn-primary btn-sm rounded-pill px-4">
            Post Comment <i class="bi bi-send-fill ms-1"></i>
          </button>
        </form>

        <!-- Comments Feed -->
        <?php if (empty($comments)): ?>
          <p class="text-muted text-center py-3">No replies yet. Be the first to start the conversation!</p>
        <?php else: ?>
          <div class="comments-feed">
            <?php foreach ($comments as $com): ?>
              <div class="d-flex gap-3 mb-4 pb-3 border-bottom align-items-start">
                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:40px; height:40px; flex-shrink:0;">
                  <?php echo substr($com['full_name'], 0, 1); ?>
                </div>
                <div>
                  <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                    <strong class="text-dark small"><?php echo sanitize($com['full_name']); ?></strong>
                    <span class="text-muted small" style="font-size:0.75rem;"><?php echo date('M d, Y \a\t g:i A', strtotime($com['created_at'])); ?></span>
                  </div>
                  <p class="text-muted small mb-0"><?php echo nl2br(sanitize($com['comment_text'])); ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Right Sidebar -->
    <div class="col-lg-4">
      <div class="card border-0 bg-light p-4 mb-4" style="border-radius:15px;">
        <h4 class="fw-bold text-dark mb-3">Recent News Topics</h4>
        <p class="small text-muted mb-3">Keep up-to-date with academic journal indexes, LaTeX guidelines, typesetting guidelines, and peer reviewer lists.</p>
        <a href="/blog.php" class="btn btn-outline-primary btn-sm rounded-pill w-100 py-2">Explore All News</a>
      </div>
      
      <div class="card border-0 p-4 text-white text-center" style="background: linear-gradient(135deg, #0f4c81 0%, #1d4ed8 100%); border-radius:15px;">
        <i class="bi bi-patch-question-fill fs-1 text-warning d-block mb-3"></i>
        <h4 class="fw-bold text-white font-title">Need Writing Help?</h4>
        <p class="small text-white-50 mb-3">Our authors and editors offer manuscript verification, guidelines review, and citation support.</p>
        <a href="/contact.php" class="btn btn-light btn-sm text-primary rounded-pill px-4 fw-semibold">Contact Editors</a>
      </div>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
