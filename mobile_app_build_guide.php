<?php
// Pretty renderer for MOBILE_APP_BUILD_GUIDE.md with a minimal Markdown converter and modern styling.
// Path to the markdown file (repo root)
$mdPath = __DIR__ . DIRECTORY_SEPARATOR . 'MOBILE_APP_BUILD_GUIDE.md';
$raw = is_file($mdPath) ? file_get_contents($mdPath) : "# Mobile App Build Guide\n\nGuide not found. Please ensure MOBILE_APP_BUILD_GUIDE.md exists at repo root.";

// Extract code blocks first to avoid mangling
$codeBlocks = [];
$raw = preg_replace_callback('/```(\w+)?\n([\s\S]*?)```/m', function($m) use (&$codeBlocks) {
    $lang = isset($m[1]) ? trim($m[1]) : '';
    $code = htmlspecialchars($m[2], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $placeholder = '[[[CODEBLOCK_' . count($codeBlocks) . ']]]';
    $codeBlocks[] = [
        'ph' => $placeholder,
        'html' => '<pre class="code"><code class="lang-'. htmlspecialchars($lang) .'">' . $code . '</code></pre>'
    ];
    return $placeholder;
}, $raw);

// Escape the rest
$html = htmlspecialchars($raw, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

// Simple markdown transformations (order matters)
$replacements = [
    // Horizontal rules
    '/^---$/m' => '<hr />',
    // Headings with emojis supported
    '/^######\s+(.+)$/m' => '<h6>$1</h6>',
    '/^#####\s+(.+)$/m'  => '<h5>$1</h5>',
    '/^####\s+(.+)$/m'   => '<h4>$1</h4>',
    '/^###\s+(.+)$/m'    => '<h3 id="$1">$1</h3>',
    '/^##\s+(.+)$/m'     => '<h2 id="$1">$1</h2>',
    '/^#\s+(.+)$/m'      => '<h1>$1</h1>',
    // Blockquotes
    '/^>\s?(.+)$/m'      => '<blockquote>$1</blockquote>',
    // Bold and italics
    '/\*\*(.+?)\*\*/s'  => '<strong>$1</strong>',
    '/\*(.+?)\*/s'       => '<em>$1</em>',
    // Inline code
    '/`([^`]+)`/'         => '<code class="inline">$1</code>',
    // Links [text](url)
    '/\[([^\]]+)\]\(([^\)]+)\)/' => '<a href="$2" target="_blank" rel="noopener">$1</a>',
];

foreach ($replacements as $pattern => $rep) {
    $html = preg_replace($pattern, $rep, $html);
}

// Lists (unordered and ordered)
// Unordered: lines starting with - or *
$html = preg_replace_callback('/(^|\n)(?:[-*])\s+.+(?:\n[-*]\s+.+)*/', function($m) {
    $block = $m[0];
    $items = preg_split('/\n/', trim($block));
    $lis = array_map(function($line) {
        return '<li>' . trim(preg_replace('/^[-*]\s+/', '', $line)) . '</li>';
    }, $items);
    return "\n<ul>" . implode('', $lis) . "</ul>";
}, $html);

// Ordered: lines starting with number.
$html = preg_replace_callback('/(^|\n)(?:\d+\.)\s+.+(?:\n\d+\.\s+.+)*/', function($m) {
    $block = $m[0];
    $items = preg_split('/\n/', trim($block));
    $lis = array_map(function($line) {
        return '<li>' . trim(preg_replace('/^\d+\.\s+/', '', $line)) . '</li>';
    }, $items);
    return "\n<ol>" . implode('', $lis) . "</ol>";
}, $html);

// Tables (very simple: pipe lines)
$html = preg_replace_callback('/(^|\n)\|(.+)\|\n\|([\-\|\s:]+)\|\n((?:\|.*\|\n?)+)/', function($m){
    $thead = array_map('trim', explode('|', trim($m[2])));
    $rowsRaw = trim($m[4]);
    $rows = array_filter(array_map('trim', explode("\n", $rowsRaw)));
    $trs = '';
    foreach ($rows as $r) {
        $cols = array_map('trim', explode('|', trim($r, "| ")));
        $tds = '';
        foreach ($cols as $c) { $tds .= '<td>' . $c . '</td>'; }
        $trs .= '<tr>' . $tds . '</tr>';
    }
    $ths = '';
    foreach ($thead as $h) { $ths .= '<th>' . $h . '</th>'; }
    return "\n<table><thead><tr>$ths</tr></thead><tbody>$trs</tbody></table>";
}, $html);

// Paragraphs: wrap loose lines
$lines = preg_split('/\n\n+/', $html);
$lines = array_map(function($chunk){
    $trim = trim($chunk);
    if ($trim === '') return '';
    if (preg_match('/^<(h\d|ul|ol|pre|table|blockquote|hr|p|img)/', $trim)) return $trim;
    return '<p>' . $trim . '</p>';
}, $lines);
$html = implode("\n\n", $lines);

// Reinstate code blocks
foreach ($codeBlocks as $cb) {
    $html = str_replace($cb['ph'], $cb['html'], $html);
}

// Build a lightweight TOC from headings h2/h3
preg_match_all('/<h(2|3) id=\"?(.*?)\"?>(.*?)<\/h\1>/', $html, $matches, PREG_SET_ORDER);
$toc = '';
$seenIds = [];
foreach ($matches as $h) {
    $level = (int)$h[1];
    $text = strip_tags($h[3]);
    // Create a safe id
    $id = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
    $id = trim($id, '-');
    if (isset($seenIds[$id])) { $seenIds[$id]++; $id .= '-' . $seenIds[$id]; } else { $seenIds[$id] = 0; }
    // Replace the heading with anchor id
    $html = str_replace($h[0], '<h'. $level .' id="'. $id .'">'. $text . ' <a class="anchor" href="#'. $id .'" aria-label="Link to this section">#</a></h'. $level .'>', $html);
    $indent = $level === 3 ? ' class="sub"' : '';
    $toc .= '<li'. $indent .'><a href="#'. $id .'">'. $text .'</a></li>';
}

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>PhoneMonitor • Mobile App Build Guide</title>
  <link rel="icon" href="/assets/icons/favicon-32.png" type="image/png" />
  <style>
    :root {
      --bg: #0b1020;
      --panel: #121a33;
      --muted: #8da2d0;
      --fg: #e9eefc;
      --accent: #7c9cff;
      --accent-2: #4de4c9;
      --code-bg: #0f1428;
      --border: #1e2a4a;
      --shadow: 0 10px 30px rgba(0,0,0,.35);
    }
    @media (prefers-color-scheme: light) {
      :root {
        --bg: #f7f9ff; --panel: #ffffff; --muted: #4a5c82; --fg: #0b1020;
        --accent: #3b6cff; --accent-2: #00c7a1; --code-bg: #f3f6ff; --border:#e6ecff;
      }
    }
    html, body { margin:0; padding:0; background: var(--bg); color: var(--fg); font: 16px/1.6 system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, Noto Sans, sans-serif; }
    .hero {
      background: radial-gradient(1200px 600px at -10% -10%, rgba(124,156,255,.25), transparent 60%),
                  radial-gradient(1000px 500px at 110% -20%, rgba(77,228,201,.20), transparent 50%),
                  linear-gradient(180deg, rgba(255,255,255,.02), rgba(255,255,255,0));
      padding: 60px 20px 30px;
      border-bottom: 1px solid var(--border);
      position: sticky; top: 0; z-index: 5; backdrop-filter: blur(6px);
    }
    .hero h1 { margin:0 0 8px; font-size: 32px; letter-spacing: .3px; }
    .hero p { margin:0; color: var(--muted); }

    .wrap { display: grid; grid-template-columns: 280px 1fr; gap: 28px; padding: 28px; max-width: 1200px; margin: 0 auto; }
    @media (max-width: 980px) { .wrap { grid-template-columns: 1fr; } .sidebar { position: static; } }

    .sidebar { position: sticky; top: 96px; align-self: start; background: var(--panel); border:1px solid var(--border); border-radius: 14px; padding: 18px; box-shadow: var(--shadow); }
    .sidebar h3 { margin: 6px 0 12px; font-size: 14px; text-transform: uppercase; letter-spacing: .12em; color: var(--muted); }
    .toc { list-style: none; padding: 0; margin: 0; }
    .toc li { margin: 6px 0; }
    .toc li.sub { margin-left: 14px; font-size: 14px; }
    .toc a { color: var(--fg); text-decoration: none; padding: 6px 8px; border-radius: 8px; display: block; }
    .toc a:hover { background: rgba(124,156,255,.12); color: var(--accent); }

    .content { background: var(--panel); border:1px solid var(--border); border-radius: 14px; padding: 26px; box-shadow: var(--shadow); }
    .content h1, .content h2, .content h3, .content h4 { scroll-margin-top: 85px; }
    .content h2 { margin-top: 28px; border-top: 1px dashed var(--border); padding-top: 18px; }
    .content h3 { margin-top: 22px; }
    .content p { margin: 12px 0; }
    .content hr { border: none; height: 1px; background: var(--border); margin: 24px 0; }
    .content blockquote { margin: 12px 0; padding: 10px 14px; border-left: 3px solid var(--accent); background: rgba(124,156,255,.08); border-radius: 0 10px 10px 0; color: var(--muted); }
    .content table { width: 100%; border-collapse: collapse; overflow: hidden; border-radius: 10px; border:1px solid var(--border); margin: 16px 0; }
    .content th, .content td { padding: 10px 12px; border-bottom: 1px solid var(--border); }
    .content thead { background: rgba(124,156,255,.08); }

    pre.code { background: var(--code-bg); padding: 14px; border-radius: 10px; overflow: auto; border:1px solid var(--border); }
    code.inline { background: var(--code-bg); padding: 2px 6px; border-radius: 6px; border:1px solid var(--border); }

    .topbar { display:flex; gap: 10px; align-items: center; margin-top: 14px; }
    .btn { display:inline-flex; align-items:center; gap:8px; padding:8px 12px; border-radius:10px; border:1px solid var(--border); background: #0f1530; color: var(--fg); text-decoration:none; box-shadow: var(--shadow); }
    .btn:hover { border-color: var(--accent); color: var(--accent); }

    .anchor { opacity: 0; margin-left: 8px; color: var(--muted); text-decoration: none; }
    h2:hover .anchor, h3:hover .anchor { opacity: 1; }

    .badges { display:flex; gap: 8px; flex-wrap: wrap; margin: 14px 0 0; }
    .badge { font-size: 12px; padding: 4px 8px; border:1px solid var(--border); border-radius: 999px; color: var(--muted); background: rgba(77,228,201,.08); }

    .footer { color: var(--muted); text-align:center; padding: 30px 20px 60px; }
  </style>
</head>
<body>
  <header class="hero">
    <div class="wrap" style="grid-template-columns: 1fr; padding:0;">
      <div>
        <h1>📱 Mobile App Build Guide</h1>
        <p>Beautiful, readable docs for Android, iOS and Kotlin Multiplatform — powered by your markdown.</p>
        <div class="topbar">
          <a class="btn" href="/MOBILE_APP_BUILD_GUIDE.md" target="_blank" rel="noopener">View raw Markdown</a>
          <a class="btn" href="/setup.php#mobile-apps">Back to Setup</a>
        </div>
        <div class="badges">
          <span class="badge">Android</span>
          <span class="badge">iOS</span>
          <span class="badge">KMM</span>
          <span class="badge">React Native</span>
          <span class="badge">Flutter</span>
        </div>
      </div>
    </div>
  </header>

  <main class="wrap">
    <aside class="sidebar">
      <h3>Contents</h3>
      <ul class="toc"><?php echo $toc ?: '<li><em>Contents will appear here</em></li>'; ?></ul>
    </aside>

    <article class="content">
      <?php echo $html; ?>
    </article>
  </main>

  <footer class="footer">
    <small>Generated from <strong>MOBILE_APP_BUILD_GUIDE.md</strong>. This page auto-updates when the markdown changes.</small>
  </footer>
</body>
</html>
