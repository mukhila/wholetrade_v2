<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CompanySettingController extends Controller
{
    public function index()
    {
        $setting = \App\Models\CompanySetting::first();
        if (!$setting) {
             return redirect()->route('company-settings.create');
        }
        return view('company_settings.index', compact('setting'));
    }

    public function create()
    {
        $setting = \App\Models\CompanySetting::first();
        if ($setting) {
             return redirect()->route('company-settings.edit', $setting->id);
        }
        return view('company_settings.create');

    }
    
    public function store(Request $request)
    {
        $this->checkStaffPermission('company-settings', 'edit');
        $request->validate([
            'company_name' => 'required',
            'logo' => 'nullable|image|max:2048',
            'authorized_signature' => 'nullable|image|max:2048',
            'email' => 'required|email',
            'gst_include' => 'nullable|in:Yes,No',
        ]);

        $data = $request->except(['logo', 'authorized_signature']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company', 'public_uploads');
        }

        if ($request->hasFile('authorized_signature')) {
            $data['authorized_signature'] = $request->file('authorized_signature')->store('company', 'public_uploads');
        }

        \App\Models\CompanySetting::create($data);

        return redirect()->route('company-settings.index')->with('success', 'Settings created successfully.');
    }


    public function edit()
    {
         $setting = \App\Models\CompanySetting::first();
         if (!$setting) {
            return redirect()->route('company-settings.create');
         }
        return view('company_settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $this->checkStaffPermission('company-settings', 'edit');
        $setting = \App\Models\CompanySetting::firstOrFail();

        $request->validate([
            'company_name'           => 'required',
            'logo'                   => 'nullable|image|max:2048',
            'authorized_signature'   => 'nullable|image|max:2048',
            'email'                  => 'required|email',
            'uen_no'                 => 'nullable|string|max:50',
            'gst_include'            => 'nullable|in:Yes,No',
            'gst_invoice_prefix'     => 'nullable|string|max:20',
            'non_gst_invoice_prefix' => 'nullable|string|max:20',
            'thermal_printer_host'   => 'nullable|string|max:100',
            'thermal_printer_port'   => 'nullable|integer|min:1|max:65535',
        ]);

        $data = $request->except(['logo', 'authorized_signature']);

        if ($request->hasFile('logo')) {
            $data['logo'] = $request->file('logo')->store('company', 'public_uploads');
        }

        if ($request->hasFile('authorized_signature')) {
            $data['authorized_signature'] = $request->file('authorized_signature')->store('company', 'public_uploads');
        }

        $setting->update($data);

        return redirect()->route('company-settings.index')->with('success', 'Settings updated successfully.');
    }
}
