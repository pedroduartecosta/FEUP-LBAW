<?php
/**
 * Created by PhpStorm.
 * User: epassos
 * Date: 4/14/17
 * Time: 10:51 PM
 */


function select(){
    echo 'Called select function';
    exit;
}

function submit_post($id_project, $id_user, $title, $content){
    global $conn;
    $stmt = $conn->prepare("INSERT INTO forum_post (title,creation_date,content,id_project,date_modified,id_creator) VALUES (?,?,?,?,?,?)");
    $date = date("Y-m-d H:i:s");
    $result = $stmt->execute(array($title, $date, $content, $id_project, $date , $id_user));
    return $result;
}

function getNumPosts($projectId){
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM forum_post WHERE id_project = ?");
    $stmt->execute($projectId);
    return $stmt->fetch()['count'];
}

function getProjectPosts($projectId,$forumPage){
    global $conn;
    $offset = ($forumPage - 1) * 5;
    $stmt = $conn->prepare("SELECT * FROM forum_post WHERE id_project = ? ORDER BY date_modified DESC LIMIT 5 OFFSET ?");
    $stmt->execute(array($projectId,$offset));
    $posts = $stmt->fetchAll();
    return $posts;
}

function getUser($userId){
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM user_table WHERE id = ?");
    $stmt->execute(array($userId));
    return $stmt->fetchAll()[0];
}

function getUserPhoto($user){
    global $BASE_DIR;
    if (!is_null($user['photo_path']) && file_exists($BASE_DIR. $user['photo_path'])) {
        return '../images/users/' . $user['photo_path'];
    }
    else {
        return '../images/assets/default_image_profile1.jpg';
    }
}

function getPostContent($projectID, $postID){
    global $conn;

    $stmt = $conn->prepare("SELECT content FROM forum_post WHERE forum_post.id = ? AND forum_post.id_project = ?");
    $stmt->execute(array($postID,$projectID));

    $result = $stmt->fetchAll()[0];
    return $result['content'];
}

/**
 * Returns the creation date, content, replier id and number of likes of every reply of a given forum post
 *
 * @param $postID id of the forum post
 * @return array creation date, content, replier id and number of likes
 */
function getReplies($postID){
    global $conn;

    $stmt = $conn->prepare(
        "SELECT creation_date,content,creator_id,n_likes
        FROM 
        (
        SELECT creation_date,content,creator_id, count(forum_reply_like.user_id) AS n_likes
        FROM forum_reply LEFT JOIN forum_reply_like ON id = reply_id
        WHERE post_id = ?
        GROUP BY id
        ) reply_info
        ORDER BY creation_date ASC"
    );
    $stmt->execute(array($postID));
    $replies = $stmt->fetchAll();
    return $replies;
}

function submitPostReply($userID, $postID, $replyContent){
    global $conn;

    $date = date("Y-m-d H:i:s");
    $stmt = $conn->prepare("INSERT INTO forum_reply (creation_date, content, post_id, creator_id) VALUES (?,?,?,?)");
    $stmt->execute(array($date,$replyContent,$postID,$userID));
    $replyID = $conn->lastInsertId();
    $stmt = $conn->prepare("SELECT * FROM forum_reply WHERE forum_reply.id = ?");
    $stmt->execute(array(intval($replyID)));
    $reply = $stmt->fetchAll()[0];
    $user = getUser($reply['creator_id']);
    $username = $user['username'];
    $photo = getUserPhoto($user);
    $output = array();
    $output['id'] = $reply['id'];
    $output['creation_date'] = $reply['creation_date'];
    $output['content'] = $reply['content'];
    $output['username'] = $username;
    $output['photo'] = $photo;

    return json_encode($output);
}