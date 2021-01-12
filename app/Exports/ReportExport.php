<?php

namespace App\Exports;

// use App\TM_MSTR_ASSET;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

class ReportExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
	
	use Exportable;
	
	public function __construct(string $datauser)
	{
        $this->datauser = $datauser;
	}
	
    public function view():View
    {
        // $dt = DB::select($this->sql);
        $dt = $this->datauser;
        $data['report'] = $dt;
        return view('report.list_user',$data);
    }
}
