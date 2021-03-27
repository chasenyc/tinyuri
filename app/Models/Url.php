<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Url extends Model
{
    use HasFactory;

    const BASE = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected $fillable = [
        'url',
    ];

    public function base62id() {
        return $this->base62($this->id);
    }

    public function scopeFromBase62($query, $base62)
    {
        $id = $this->to10($base62);

        return $query->where('id', $id);
    }

    private function base62($num) {
        $res = '';

        do {
          $res = Url::BASE[$num % 62] . $res;
          $num = intval($num / 62);
        } while ($num);

        return $res;
    }

    private function to10($num) {
        $limit = strlen($num);
        $res = strpos(Url::BASE, $num[0]);

        for($i = 1; $i < $limit; $i++) {
          $res = 62 * $res + strpos(Url::BASE, $num[$i]);
        }

        return $res;
    }
}
