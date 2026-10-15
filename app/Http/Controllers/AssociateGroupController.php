<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AssociateGroupController extends Controller
{
    public function index()
    {
        $groups = \App\Models\AssociateGroup::all();
        
        return view('associate_groups.index', compact('groups'));
    }

    public function create()
    {
        return view('associate_groups.create');
    }

    public function store(Request $request)
    {
        $this->checkStaffPermission('associate-groups', 'create');
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $status = $request->status ? true : false;

        \App\Models\AssociateGroup::create([
            'name' => $request->name,
            'status' => $status,
        ]);

        return redirect()->route('associate-groups.index')->with('success', 'Associate Group created successfully.');
    }

    public function edit($id)
    {
        $group = \App\Models\AssociateGroup::findOrFail($id);
        return view('associate_groups.edit', compact('group'));
    }

    public function update(Request $request, $id)
    {
        $this->checkStaffPermission('associate-groups', 'edit');
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $group = \App\Models\AssociateGroup::findOrFail($id);
        $status = $request->status ? true : false;
        
        $group->update([
            'name' => $request->name,
            'status' => $status,
        ]);

        return redirect()->route('associate-groups.index')->with('success', 'Associate Group updated successfully.');
    }

    public function destroy($id)
    {
        $this->checkStaffPermission('associate-groups', 'delete');
        $group = \App\Models\AssociateGroup::findOrFail($id);
        $group->delete();

        return redirect()->route('associate-groups.index')->with('success', 'Associate Group deleted successfully.');
    }
}
