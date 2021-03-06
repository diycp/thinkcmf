<?php
namespace app\portal\service;

use app\portal\model\PortalPostModel;

class PostService
{

    public function adminArticleList($filter)
    {
        return $this->adminPostList($filter);
    }

    public function adminPageList($filter)
    {
        return $this->adminPostList($filter, true);
    }

    public function adminPostList($filter, $isPage = false)
    {

        $where = [
            'a.create_time' => ['>=', 0],
            'a.delete_time' => ['eq', 0]
        ];

        $join = [
            ['__USER__ u', 'a.user_id = u.id']
        ];

        $category = empty($filter['category']) ? 0 : intval($filter['category']);
        if (!empty($category)) {
            $where['b.category_id'] = ['eq', $category];
            array_push($join, [
                '__PORTAL_CATEGORY_POST__ b', 'a.id = b.post_id'
            ]);
        }

        $startTime = empty($filter['start_time']) ? 0 : strtotime($filter['start_time']);
        $endTime   = empty($filter['end_time']) ? 0 : strtotime($filter['end_time']);
        if (!empty($startTime) && !empty($endTime)) {
            $where['a.published_time'] = [['>= time', $startTime], ['<= time', $endTime]];
        } else {
            if (!empty($startTime)) {
                $where['a.published_time'] = ['>= time', $startTime];
            }
            if (!empty($endTime)) {
                $where['a.published_time'] = ['<= time', $endTime];
            }
        }

        $keyword = empty($filter['keyword']) ? '' : $filter['keyword'];
        if (!empty($keyword)) {
            $where['a.post_title'] = ['like', "%$keyword%"];
        }

        if ($isPage) {
            $where['a.post_type'] = 2;
        } else {
            $where['a.post_type'] = 1;
        }

        $portalPostModel = new PortalPostModel();
        $articles        = $portalPostModel->alias('a')->field('a.*,u.user_login,u.user_nickname,u.user_email')
            ->join($join)
            ->where($where)
            ->paginate(10);

        return $articles;

    }

    public function publishedArticle($postId, $categoryId)
    {

        $where = [
            'post.post_type'       => 1,
            'post.published_time'  => [['< time', time()], ['> time', 0]],
            'post.post_status'     => ['eq', 1],
            'relation.category_id' => $categoryId,
            'relation.post_id'     => $postId
        ];

        $join            = [
            ['__PORTAL_CATEGORY_POST__ relation', 'post.id = relation.post_id']
        ];
        $portalPostModel = new PortalPostModel();
        $article         = $portalPostModel->alias('post')->field('post.*')
            ->join($join)
            ->where($where)
            ->find();

        return $article;
    }

}