<?php
namespace App\Http\Traits;

use App\Models\ColorCode;
use App\Models\Like;
use App\Models\ModelHasCategory;
use App\Models\ModelHasTopic;
use App\Models\Question;
use App\Models\Save;
use App\Models\Share;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserLike;
use App\Models\UserSave;
use App\Models\UserShare;
use App\Models\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait CommonTrait
{
    public function upload_images_insert_db($model, $model_id, $path)
    {
        if(empty($this->request->images)){
            return false;
        }
        Log::info(sizeof($this->request->images));
        $images = [];
        for ($i = 0; $i < sizeof($this->request->images); $i++){
            $name = uniqid() . "." . $this->request->file("images.{$i}")->getClientOriginalExtension();
            $this->request->file("images.{$i}")->storePubliclyAs('/public'.$path,$name);
            $images [] = [
                'model_id' => $model_id,
                'model_type' => $model,
                'images' => $path . $name,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ];
        }
        DB::table('media')->insert($images);
    }

    public function model_category($model, $model_id)
    {
        ModelHasCategory::create([
            'model_type' => $model,
            'model_id' => $model_id,
            'category_id' => $this->request->category_id,
        ]);
    }

    public function store_like_work()
    {
        DB::transaction(function (){
            $operator = '-';
            $data = ['model_id' => $this->request->id, 'model_type' => $this->model_type, 'user_id' => auth()->user()->id];
            if(!UserLike::where($data)->delete()){
                UserLike::create($data);
                $operator = '+';
            }
            Like::updateOrCreate(
                [
                    'model_id' => $this->request->id,
                    'model_type' => $this->model_type,
                ],
                [
                    'total' => DB::raw("total {$operator} 1")
                ]
            );
        });
    }
    public function store_save_work(){
        DB::transaction(function (){
            $operator = '-';
            $data = ['model_id' => $this->request->id, 'model_type' => $this->model_type, 'user_id' => auth()->user()->id];
            if(!UserSave::where($data)->delete()){
                UserSave::create($data);
                $operator = '+';
            }
            Save::updateOrCreate(
                [
                    'model_id' => $this->request->id,
                    'model_type' => $this->model_type,
                ],
                [
                    'total' => DB::raw("total {$operator} 1")
                ]
            );
        });
    }

    public function store_share_work()
    {
        DB::transaction(function (){
            $operator = '-';
            $data = ['model_id' => $this->request->id, 'model_type' => $this->model_type, 'user_id' => auth()->user()->id];
            if(!UserShare::where($data)->delete()){
                UserShare::create($data);
                $operator = '+';
            }
            Share::updateOrCreate(
                [
                    'model_id' => $this->request->id,
                    'model_type' => $this->model_type,
                ],
                [
                    'total' => DB::raw("total {$operator} 1")
                ]
            );
        });
    }

    public function store_view_work()
    {
        DB::transaction(function (){
            View::updateOrCreate(
                [
                    'model_id' => $this->request->id,
                    'model_type' => $this->model_type,
                ],
                [
                    'total' => DB::raw('total + 1')
                ]
            );
        });
    }

    public function color_code($model_id)
    {
        if (!$this->request->has('color')){
            return false;
        }
        ColorCode::create([
            'model_type' => $this->model_type,
            'model_id' => $model_id,
            'color' => $this->request->color,
        ]);
    }

    public function model_topic($model, $model_id)
    {
        /*foreach ($this->request->topic_id as $topic_id) {
            ModelHasTopic::create([
                'model_type' => $model,
                'model_id' => $model_id,
                'topic_id' => Topic::where('name','Default')->first()->id ?? 1,
            ]);
        }*/
        ModelHasTopic::create([
            'model_type' => $model,
            'model_id' => $model_id,
            'topic_id' => Topic::where('name','Default')->first()->id ?? 1,
        ]);
    }

    public function model_topic_update($model, $model_id)
    {
        /*foreach ($this->request->topic_id as $topic_id) {
            ModelHasTopic::create([
                'model_type' => $model,
                'model_id' => $model_id,
                'topic_id' => Topic::where('name','Default')->first()->id ?? 1,
            ]);
        }*/
        ModelHasTopic::where([
            'model_type' => $model,
            'model_id' => $model_id,
        ])->delete();
        ModelHasTopic::create([
            'model_type' => $model,
            'model_id' => $model_id,
            'topic_id' => Topic::where('name','Default')->first()->id ?? 1,
        ]);
    }
}
