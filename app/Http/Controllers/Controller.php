<?php

namespace App\Http\Controllers;

use App\Task;
use App\User;
use Illuminate\Support\Facades\Auth;
use Validator;
use Response;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function users() {
        return User::paginate(15);
    }

    public function register(Request $request) {
        $validation = Validator::make($request->all(), [
            'email' => 'required|unique:users',
            'password' => 'required|confirmed',
            'name' => 'required',
            'profile_photo' =>'mimes:jpeg,bmp,png',
        ]);

        if ($validation->fails()) {
            return Response::json([
                'status' => 0,
                'msg' => 'برجاء ملئ جميع الحقول',
                'errors' => $validation->errors()
            ], 200);
        }

        $userToken = str_random(60);
        $request->merge(array('api_token' => $userToken));
        $request->merge(array('password' => bcrypt($request->password)));
        $user = User::create($request->all());

        $profile_photo_name = 'profile_photo';
        if($request->hasFile($profile_photo_name))
        {
            $path = base_path();
            $destinationPath = $path.'/uploads/trucks/'; // upload path
            $photo= $request->file($profile_photo_name);
            $extension = $photo->getClientOriginalExtension(); // getting image extension
            $name = time().''.rand(11111,99999).'.'.$extension; // renameing image
            $photo->move($destinationPath, $name); // uploading file to given path
            $user->profile_photo = 'uploads/trucks/'.$name;
            $user->save();
        }

//        $request->merge(array('profile_photo' => $photo_path));
        if ($user) {
            $data = [
                'status' => 1,
                'msg' => 'تم التسجيل بنجاح',
                'api_token' => $userToken,
                'user' => $user
            ];
            return Response::json($data, 200);
        } else {
            return Response::json([
                'status' => 0,
                'msg' => 'حدث خطأ ، حاول مرة أخرى',
            ], 200);
        }
    }

    public function login(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if ($validation->fails()) {
            return Response::json([
                'status' => 0,
                'msg' => 'complete fields',
                'errors' => $validation->errors()
            ], 200);
        }

        $validUser = Auth::validate($request->all());
        if ($validUser) {
            $user = User::where('email', $request->input('email'))->first();
            $data = [
                'status' => 1,
                'user' => $user,
                'msg' => 'login succeed'
            ];
            return Response::json($data, 200);
        } else {
            return Response::json([
                'status' => 0,
                'msg' => 'invalid credentials',
            ], 200);
        }
    }

    public function tasks(Request $request) {
        $user = Auth::guard('api')->user();

        $per_page = 15;
        if ($request->has('per_page')) {
            $per_page = $request->per_page;
        }

        return $user->tasks()->paginate($per_page);
    }

    public function new_task(Request $request) {
        $user = Auth::guard('api')->user();
        $validation = Validator::make($request->all(), [
            'task' => 'required'
        ]);

        if ($validation->fails()) {
            return Response::json([
                'status' => 0,
                'msg' => 'complete fields',
                'errors' => $validation->errors()
            ], 200);
        }

        $task = $user->tasks()->create($request->all());

        return Response::json([
            'status' => 1,
            'msg' => 'new task created',
            'task' => $task
        ]);
    }

    public function edit_task(Request $request) {
        $user = Auth::guard('api')->user();
        $validation = Validator::make($request->all(), [
            'task_id' => 'required'
        ]);

        if ($validation->fails()) {
            return Response::json([
                'status' => 0,
                'msg' => 'complete fields',
                'errors' => $validation->errors()
            ], 200);
        }

        $task = Task::find($request->task_id);

        if(!$task) {
            return Response::json([
                'status' => 0,
                'msg' => 'can not find task with task_id'
            ]);
        }

        if ($task->user_id != $user->id) {
            return Response::json([
                'status' => 0,
                'msg' => 'you are not authorized to edit this task'
            ]);
        }

        $task->update($request->all());

        return Response::json([
            'status' => 1,
            'msg' => 'task edited',
            'task' => $task
        ]);
    }

    public function delete_task(Request $request) {
        $user = Auth::guard('api')->user();
        $validation = Validator::make($request->all(), [
            'task_id' => 'required'
        ]);

        if ($validation->fails()) {
            return Response::json([
                'status' => 0,
                'msg' => 'complete fields',
                'errors' => $validation->errors()
            ], 200);
        }

        $task = Task::find($request->task_id);

        if(!$task) {
            return Response::json([
                'status' => 0,
                'msg' => 'can not find task with task_id'
            ]);
        }

        if ($task->user_id != $user->id) {
            return Response::json([
                'status' => 0,
                'msg' => 'you are not authorized to delete this task'
            ]);
        }

        $task->delete();

        return Response::json([
            'status' => 1,
            'msg' => 'task deleted',
            'task_id' => $request->task_id
        ]);
    }
}
