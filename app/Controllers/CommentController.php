<?php

class CommentController extends Controller
{
    public function apiAdd()
    {
        $user = api_require_role('user');
        api_require_method('POST');

        $input = json_input();
        api_csrf_guard($input);

        $postId = isset($input['post_id']) ? (int) $input['post_id'] : 0;
        $body = isset($input['body']) ? trim((string) $input['body']) : '';

        // The form pre-fills the name from the profile and lets it be edited.
        // There is nowhere in the comments table to keep a per-note name, so an
        // edited one updates the profile the notes are credited to.
        $name = isset($input['name']) ? trim((string) $input['name']) : '';

        if ($postId <= 0 || $body === '') {
            json_error('Missing post ID or comment body.', 400);
        }

        if ($name === '') {
            json_error('Enter the name to post under.', 400);
        }
        if (mb_strlen($name) > 100) {
            json_error('That name is too long.', 400);
        }

        if ($name !== $user['name']) {
            User::updateName((int) $user['id'], $name);
            $_SESSION['user_name'] = $name;
            $user['name'] = $name;
        }
        if (mb_strlen($body) < 3) {
            json_error('That is a little short - add a few more words.', 400);
        }
        if (mb_strlen($body) > Comment::MAX_LENGTH) {
            json_error('Keep it under ' . Comment::MAX_LENGTH . ' characters.', 400);
        }

        if (Post::findApproved($postId) === null) {
            json_error('That destination is not published.', 404);
        }

        $commentId = Comment::create($postId, (int) $user['id'], $body);
        $count = Comment::countForPost($postId);

        json_out([
            'ok' => true,
            'message' => 'Note posted.',
            'count' => $count,
            'comment' => [
                'id' => $commentId,
                'avatar' => avatar_url(isset($user['profile_picture']) ? $user['profile_picture'] : null),
                'initial' => initial($user['name']),
                'author' => $user['name'],
                'when' => 'Just now',
                'content' => $body,
                'own' => true
            ]
        ]);
    }

    public function apiDelete()
    {
        $user = api_require_role();
        api_require_method('DELETE');

        $input = json_input();
        api_csrf_guard($input);

        // the id travels in the path: DELETE /api/comments/12
        $commentId = (int) $this->param('id');

        if ($commentId <= 0) {
            json_error('Missing comment ID.', 400);
        }

        $comment = Comment::findById($commentId);
        if (!$comment) {
            json_error('Comment not found.', 404);
        }

        // A writer may remove their own note; an admin may remove any note.
        if ($user['role'] !== 'admin' && (int) $comment['user_id'] !== (int) $user['id']) {
            json_error('You cannot delete this comment.', 403);
        }

        Comment::delete($commentId);
        $count = Comment::countForPost((int) $comment['post_id']);
        $total = Comment::countAll();

        json_out([
            'ok'      => true,
            'message' => 'Comment deleted.',
            'count'   => $count,
            'total'   => $total
        ]);
    }
}
