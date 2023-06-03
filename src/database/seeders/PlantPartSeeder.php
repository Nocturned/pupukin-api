<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlantPartSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $plantParts = array(
            "Akar",
            "Batang",
            "Daun",
            "Bunga",
            "Buah",
            "Biji"
        );

        $pictures = array(
            "https://storage.googleapis.com/pupukin-bucket/4f6dd4f000029da89507cb092aa6bacf_6479bf385aea6.png",
            "https://storage.googleapis.com/pupukin-bucket/fujiikaze_6479b8df6a4f6.jpeg",
            "https://storage.googleapis.com/pupukin-bucket/mustache_6479c8c244242.png",
            "https://storage.googleapis.com/pupukin-bucket/go_6479d5354e298.png",
            "https://storage.googleapis.com/pupukin-bucket/vitogeraldolraldo_6479d5a7f1211.gif"
        );

        foreach($plantParts as $plantpart){
            DB::table('plant_parts')->insert([
            'name' => $plantpart,
            'picture' => $pictures[array_rand($pictures)],
            'created_at' => date('Y-m-d H:i:sO', time()),
            'updated_at' => date('Y-m-d H:i:sO', time())
        ]);
        }
    }
}
