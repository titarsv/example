<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Cartalyst\Sentinel\Native\Facades\Sentinel;
use Illuminate\Http\Request;
use App\Models\Review;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\File;
use App\Models\Action;
use App\Models\UserData;
use App\Models\Product;
use Carbon\Carbon;
use TelegramBot\Api\Client;
use Illuminate\Support\Facades\Mail;

class ReviewsController extends Controller
{
    private $user;

    function __construct(){
        $this->user = Sentinel::check();
    }

    public function adminIndexAction(){
        return view('admin.reviews.index')
            ->with(['localization' => json_encode(['datatable' => trans('datatable')])]);
    }

    public function adminListAction(Request $request){
        $query = Review::select('*');

        if($request->has('search.value')){
            $text = $request->search['value'];
            $query->where('review', 'like', '%'.$text.'%')
                ->orWhere('author', 'like', '%'.$text.'%');
        }

        if($request->has('order')){
            foreach($request->order as $order){
                $query->orderBy($request->columns[$order['column']]['name'], $order['dir']);
            }
        }

        $records_filtered = $query->count();

        if($request->length > 0){
            $query->offset($request->start)
                ->limit($request->length);
        }

        $reviews = $query->get();

        $data = [];
        foreach($reviews as $review){
            $actions = [];
            if($this->user->hasAccess(['reviews.write'])){
                $actions[] = [
                    'type' => 'edit',
                    'link' => asset('admin/reviews/products/edit/'.$review->id)
                ];
            }
            if($this->user->hasAccess(['articles.delete'])){
                $actions[] = [
                    'type' => 'delete',
                    'id' => $review->id,
                    'name' => $review->author
                ];
            }

            $data[] = [
                'id' => $review->id,
                'author' => $review->author,
                'grade' => $review->grade,
                'published' => ['id' => $review->id, 'status' => (bool)$review->published],
                'created_at' => $review->created_at->format('d.m.Y H:i'),
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => Review::count(),
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    public function adminEditAction($id){
        $review = Review::find($id);

        return view('admin.reviews.edit', ['review' => $review]);
    }

    public function adminUpdateAction(Request $request, $id){
        $review = Review::find($id);

        $review->update(['published' => $request->published, 'answer' =>$request->answer, 'new' => 0]);

        return redirect('/admin/reviews')
            ->with('message-success', trans('locale.messages.review_updated'));
    }

    /**
     * Публикация / снятие с публикации отзыва
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateStatusAction(Request $request, $id){
        $review = Review::find($id);

        if(empty($review)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.review_not_found')], 200);
        }
        $review_data = $review->fullData();
        $review->published = (int)$request->status;
        $review->new = 0;
        $review->save();

        $product = Product::find($review->product_id);
        $product_rating = $product->reviews()->where('published', 1)->avg('grade');
        $product->update(['rating' => $product_rating]);

        Action::updateEntity($review->find($id), $review_data);

        if($review->published)
            return response()->json(['result' => 'success', 'message' => trans('locale.messages.review_published')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.messages.review_unpublished')], 200);
    }

    /**
     * Добавление / удаление отзыва из избранного
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateFavoriteStatusAction(Request $request, $id){
        $review = Review::find($id);

        if(empty($review)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.review_not_found')], 200);
        }
        $review_data = $review->fullData();
        $review->favorite = (int)$request->status;
        $review->save();

        Action::updateEntity($review->find($id), $review_data);

        if($review->favorite)
            return response()->json(['result' => 'success', 'message' => trans('locale.messages.review_added_to_favorites')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.messages.review_removed_from_favorites')], 200);
    }

    /**
     * Обновление ответа на коментарий
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateAnswerAction(Request $request, $id){
        $review = Review::find($id);

        if(empty($review)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.review_not_found')], 200);
        }
        $review_data = $review->fullData();
        $review->answer = $request->answer;
        $review->save();

        Action::updateEntity($review->find($id), $review_data);

        if($review->answer)
            return response()->json(['result' => 'success', 'message' => empty($review_data['answer']) ? trans('locale.messages.review_answer_sent') : trans('locale.messages.review_answer_updated')], 200);
        else
            return response()->json(['result' => 'warning', 'message' => trans('locale.messages.review_answer_deleted')], 200);
    }

    /**
     * Обновление прикреплённых файлов коментария
     *
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function adminUpdateMediaAction(Request $request, $id){
        $review = Review::find($id);

        if(empty($review)){
            return response()->json(['result' => 'error', 'message' => trans('locale.messages.review_not_found')], 200);
        }
        $review_data = $review->fullData();
        $review->saveGalleries(!empty($request->gallery) ? $request->gallery : []);

        Action::updateEntity($review->find($id), $review_data);

        if(!empty($request->gallery)){
            return response()->json(['result' => 'success', 'message' => trans('locale.messages.files_updated')], 200);
        }else{
            return response()->json(['result' => 'warning', 'message' => trans('locale.messages.files_deleted')], 200);
        }
    }

    public function adminDestroyAction($id){
        $review = Review::find($id);
        $review->delete();

        return response()->json(['result' => 'success', 'message' => trans('locale.messages.review_deleted')], 200);
    }

    public function addAction(Request $request, Review $review, UserData $user_data, Setting $setting)
    {
        $rules = [
            'product_id' => 'required',
            'review' => 'required',
            'name' => 'required',
            'email' => 'required|email',
        ];

        $messages = [
            'review.required'   => trans('validation.review_required'),
            'name.required'     => trans('validation.name_required'),
            'email.required'    => trans('validation.email_required'),
            'email.email'       => trans('validation.email_valid')
        ];

        if($request->type == 'review') {
            $rules['grade'] = 'required';
            $messages['grade.required'] = trans('validation.grade_required');
        }

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->messages(), 'type' => $request->type], 200);
        }

        $user = User::where('email', $request->email)->first();

        if($user == null) {
            $user = Sentinel::registerAndActivate(array(
                'email'    => $request->email,
                'password' => 'null',
                'first_name' => $request->name,
                'permissions' => [
                    'unregistered' => true
                ]
            ));

            $role = Sentinel::findRoleBySlug('unregistered');
            $role->users()->attach($user);

            $user_data->create([
                'user_id'   => $user->id,
                'subscribe' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
        }

        $review->fill($request->except('_token'));
        $review->user_id = $user->id;
        $review->product_id = $request->product_id;
        $review->published = 0;
        $review->new = 1;
        $review->author = $request->name;
        $review->answer = '';
        $review->save();

        $subject = trans('emails.new_review_subject', ['app_name' => env('APP_NAME')]);
        $emails = [];
        foreach($setting->get_setting('emails') as $email){
            if(in_array($email->destination, ['orders', 'all'])){
                $emails[] = $email->email;
            }
        }
        if(!empty($emails)){
            Mail::send('emails.review', ['review' => $review, 'page' => $request->url], function($msg) use ($emails, $subject){
                $msg->from('admin@'.str_replace(['http://', 'https://'], '', env('APP_URL')), trans('emails.online_store', ['app_name' => env('APP_NAME')]));
                $msg->to($emails);
                $msg->subject($subject);
            });
        }

        $settings = new Setting();
        $telegram = (array)$settings->get_setting('telegram');
        if(!empty($telegram['token'])){
            $bot = new Client($telegram['token']);

            $text = trans('telegram.new_review')."\n";
            $text .= trans('telegram.email').": ".$review->user->email."\n";
            $text .= trans('telegram.review_text').": ".$review->review."\n";

            foreach($telegram['clients'] as $id => $client){
                if($client->moderated){
                    $bot->sendMessage($client->chat, $text);
                }
            }
        }

        return response()->json(['success' => trans('locale.messages.review_added_success'), 'type' => $request->type]);
    }
}
