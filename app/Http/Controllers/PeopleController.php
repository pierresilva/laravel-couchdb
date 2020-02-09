<?php

namespace App\Http\Controllers;

use App\Person;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PeopleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
        $people = Person::all();

        return $people;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        //

        $validation = \Validator::make($request->all(), [
            'username' => 'required|unique_couchdb:App\Person,username',
            'firstname' => 'required',
            'lastname' => 'required',
        ],
            [
                'unique_couchdb' => 'The :attribute field is taken!'
            ]);

        if ($validation->fails()) {
            return [
                'error' => $validation->errors()
            ];
        }

        $person = Person::create($request->all());

        return $person;
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        //

        $person = Person::find($id);

        return $person;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //

        $validation = \Validator::make($request->all(), [
            'username' => 'required|unique_couchdb:App\Person,username,_id,' . $id,
            'firstname' => 'required',
            'lastname' => 'required',
        ],
            [
                'unique_couchdb' => 'The :attribute field is taken!'
            ]);

        if ($validation->fails()) {
            return [
                'error' => $validation->errors()
            ];
        }

        $person = Person::findOrFail($id);

        $person->update($request->all());

        return $person;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
        $person = Person::find($id);

        $person->destroy($id);

        return $person;
    }
}
