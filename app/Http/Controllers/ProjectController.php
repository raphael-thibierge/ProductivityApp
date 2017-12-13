<?php

namespace App\Http\Controllers;

use App\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $projects = Auth::user()->projects()->with('goals')->get();

        return $this->successResponse([
            'projects' => $projects,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
        ]);

        $project = Auth::user()->projects()->create([
            "title" => $request->get('title'),
            'is_archived' => false,
        ]);

        // by default goals is null, so must be init before sending it to UI
        $project->goals = [];


        return $this->successResponse([
            'project'  => $project
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function show(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Project $project)
    {

        $this->validate($request, [
            'title' => 'required|string',
            'is_archived' => 'required|string|in:true,false'
        ]);

        $project->update([
            'title' => $request->get('title'),
            'is_archived' => $request->get('is_archived') == 'true' ? true : false,
        ]);

        return $this->successResponse([
            'project' => $project
        ]);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Project  $project
     * @return \Illuminate\Http\Response
     */
    public function destroy(Project $project)
    {
        //
    }
}
