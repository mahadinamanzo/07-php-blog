<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/app/bootstrap.php';

$adminId = db()->query('SELECT id FROM admins ORDER BY id LIMIT 1')->fetchColumn();
if ($adminId === false) {
    fwrite(STDERR, "No admin account exists. Run create-admin.php before seeding posts.\n");
    exit(1);
}

$seedCategories = [
    'tutorials' => ['Tutorials', 'Practical guides and walkthroughs.'],
    'news' => ['News', 'Updates and ideas from the community.'],
    'career' => ['Career', 'Skills and perspectives for growing your career.'],
];
$findCategory = db()->prepare('SELECT id FROM categories WHERE slug = :slug OR name = :name LIMIT 1');
$insertCategory = db()->prepare(
    'INSERT INTO categories (name, slug, description) VALUES (:name, :slug, :description)'
);
$categories = [];
foreach ($seedCategories as $slug => [$name, $description]) {
    $findCategory->execute(['slug' => $slug, 'name' => $name]);
    $categoryId = $findCategory->fetchColumn();
    if ($categoryId === false) {
        $insertCategory->execute(['name' => $name, 'slug' => $slug, 'description' => $description]);
        $categoryId = db()->lastInsertId();
    }
    $categories[$slug] = (int) $categoryId;
}

$posts = [
    ['tutorials', 'A Calm, Practical Guide to Planning Your Week', 'A flexible weekly plan can bring focus to busy days without turning every hour into a deadline.', "A useful weekly plan starts with a short pause. Look at the commitments already on your calendar, then decide which two or three outcomes would make the week feel meaningful.\n\nGive focused work a place on the calendar, but leave room between tasks. A little open space makes it easier to handle the unexpected without losing the whole day.\n\nAt the end of the week, notice what helped and what got in the way. Treat the plan as a tool you can adjust, not a scorecard."],
    ['news', 'What We Learned from Building in Public', 'Sharing work in progress creates better conversations and turns small lessons into a resource for everyone.', "Building in public does not mean sharing every unfinished thought. It means making useful parts of the process visible: the problem, the trade-offs, and what changed after feedback.\n\nA short update with a clear lesson is often more helpful than a polished announcement. Readers can see how a decision took shape and add their own experience.\n\nWe have found that this habit makes the work more connected to the people it is meant to serve."],
    ['career', 'How to Make a Portfolio Tell a Clear Story', 'A focused portfolio helps people understand not only what you made, but how you think and work.', "Choose a small number of projects that show different strengths. For each one, explain the challenge, your role, the decisions you made, and the result.\n\nContext matters. A screenshot is more useful when a reader knows what changed and why. Be clear about collaboration, constraints, and what you would improve next time.\n\nA portfolio is a conversation starter. Make it easy for someone to see where you can contribute."],
    ['tutorials', 'A Beginner-Friendly Approach to Better Notes', 'Simple, searchable notes are easier to maintain than a complicated system that asks for constant upkeep.', "Start with one reliable place for notes and a few clear categories. Capture ideas in plain language while they are fresh; you can organize them later when a pattern becomes clear.\n\nUse titles that describe the decision, question, or topic. Add links to related notes instead of copying the same explanation into several places.\n\nOnce a week, spend a few minutes reviewing open questions and moving useful ideas into your current work."],
    ['news', 'Small Improvements That Make Websites Feel Faster', 'Perceived speed comes from thoughtful loading states, efficient assets, and keeping the important content easy to reach.', "A page feels responsive when it acknowledges an action quickly. Clear button feedback and useful loading messages help people understand what is happening while work continues.\n\nImage dimensions, font choices, and unnecessary scripts all affect how quickly a page becomes usable. Measure real pages before optimizing so effort goes toward the biggest delays.\n\nFast experiences are not only about benchmarks. They also reduce uncertainty and keep people oriented."],
    ['career', 'Preparing for a Useful Mentorship Conversation', 'A little preparation helps mentors give specific advice and makes the conversation valuable for both people.', "Bring one question grounded in a real situation. Explain what you have tried, what constraints you are working within, and what kind of perspective would help.\n\nListen for reasoning rather than a perfect formula. A mentor's example can reveal principles you can adapt to your own context.\n\nAfterward, send a brief note about what you will try. Following through turns one conversation into lasting progress."],
    ['tutorials', 'Making a Reading List You Will Actually Use', 'A manageable reading list connects what you read to questions you are already exploring.', "Instead of collecting every interesting link, keep a short queue and add a sentence about why each item matters. That note makes it easier to choose what to read when you have time.\n\nGroup resources around current projects or questions. When an item is no longer relevant, remove it without guilt; a reading list is a working tool, not an archive of obligations.\n\nAfter reading, write down one idea you want to test or share."],
    ['news', 'Designing More Inclusive Community Events', 'Clear expectations, accessible formats, and thoughtful follow-up help more people participate comfortably.', "Share practical details early: the schedule, location or access link, available accommodations, and how participants can ask for support.\n\nOffer more than one way to take part. Some people prefer speaking, while others contribute best in writing or smaller groups. Make breaks and quiet space part of the plan.\n\nAsk for feedback afterward and explain what you will change. Inclusion improves through specific, visible follow-through."],
    ['career', 'Turning Feedback into a Practical Next Step', 'Useful feedback becomes easier to act on when you separate the observation from the solution and choose one experiment.', "Listen for the specific behavior or outcome behind a comment. If something is unclear, ask for an example and what a stronger result might look like.\n\nYou do not need to apply every suggestion at once. Choose one change that fits your goals, try it in a real situation, and observe what happens.\n\nReview the experiment with the person who offered feedback. That closes the loop and makes future conversations more precise."],
    ['tutorials', 'A Simple Checklist for Publishing a Blog Post', 'A short pre-publish routine catches avoidable problems while keeping the writing process straightforward.', "Read the title and opening together. They should make the topic and value clear without promising more than the article delivers.\n\nCheck links, spelling, paragraph breaks, and the cover image on a small screen. Confirm the category and publication status, then preview the public page.\n\nAfter publishing, verify the live link and share it where the intended readers can find it."],
    ['news', 'Why Sustainable Pace Is a Team Skill', 'Teams do better work when priorities, capacity, and recovery are discussed openly instead of treated as individual problems.', "Sustainable pace begins with honest priorities. When everything is urgent, teams need a way to decide what can wait and what should be removed from the plan.\n\nRegular check-ins can surface blocked work and overloaded schedules early. They are most useful when people can raise concerns without being blamed for them.\n\nRest is not separate from delivery. Protecting time to recover helps teams make thoughtful decisions over the long term."],
    ['career', 'Building Confidence Through Deliberate Practice', 'Confidence grows when practice is specific, repeatable, and followed by reflection rather than waiting for perfect conditions.', "Pick one skill and define a small exercise that lets you use it regularly. A focused practice session is easier to repeat than a vague promise to get better.\n\nKeep a simple record of what you tried, what felt difficult, and what changed. Progress often appears in those details before it feels dramatic.\n\nAsk for feedback on the next step, then adjust the exercise. Consistent practice turns uncertainty into experience."],
];

$pdo = db();
$pdo->beginTransaction();
try {
    $findPost = $pdo->prepare('SELECT id FROM posts WHERE title = :title LIMIT 1');
    $insertPost = $pdo->prepare(
        "INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, published_at)
         VALUES (:category_id, :author_id, :title, :slug, :excerpt, :body, 'published', NOW())"
    );

    $created = 0;
    $skipped = 0;
    foreach ($posts as [$categorySlug, $title, $postExcerpt, $body]) {
        $findPost->execute(['title' => $title]);
        if ($findPost->fetchColumn() !== false) {
            $skipped++;
            continue;
        }

        $slug = unique_slug($title, 'posts');
        $insertPost->execute([
            'category_id' => $categories[$categorySlug],
            'author_id' => (int) $adminId,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $postExcerpt,
            'body' => $body,
        ]);
        $created++;
    }
    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

fwrite(STDOUT, "Seed complete: {$created} posts created, {$skipped} existing seed posts skipped.\n");
