<?php

namespace App\Models;

use CodeIgniter\Model;

class NewsModel extends Model
{
    protected $table = 'web_news';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'title',
        'content',
        'type',
        'image_url',
        'file_url',
        'file_name',
        'created_at',
    ];

    protected $useTimestamps = false;

    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[255]',
        'content' => 'required',
        'type' => 'required|in_list[info,update,warning]',
    ];

    /**
     * Get latest news
     */
    public function getLatest(int $limit = 10): array
    {
        return $this->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->findAll();
    }

    /**
     * Get news by type
     */
    public function getByType(string $type): array
    {
        return $this->where('type', $type)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
