<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ChunCsvOptoutJob;
use App\Jobs\ChunkCsvClientJob;
use App\Jobs\ChunkCsvJob;
use App\Models\JobProgress;
use App\Models\LeadspeekUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function suppressionupload(Request $request) 
    {
        $leadspeek_apiID = $request->leadspeekID;
        $campaigntype = (isset($request->campaigntype))?$request->campaigntype:'campaign';

        /* CONFIGURATION FOR SPACES */
        $filenameOri = $request->file('suppressionfile')->getClientOriginalName();
        $filename = pathinfo($filenameOri, PATHINFO_FILENAME);
        $extension = $request->file('suppressionfile')->getClientOriginalExtension();
        $tmpfile = Carbon::now()->valueOf() . '_leadspeek_' . $leadspeek_apiID . '.' . $extension; 
        /* CONFIGURATION FOR SPACES */

        /* UPLOAD TO DO SPACES */
        $path = Storage::disk('spaces')->putFileAs('suppressionlist', $request->file('suppressionfile'), $tmpfile);
        $filedownload_url = Storage::disk('spaces')->url($path);
        $filedownload_url = str_replace('digitaloceanspaces', 'cdn.digitaloceanspaces', $filedownload_url);
        /* UPLOAD TO DO SPACES */

        /* GET LEADSPEEK USER */
        $leaduser = LeadspeekUser::select('leadspeek_users.id','users.company_id','users.company_parent','leadspeek_users.leadspeek_api_id')
                                 ->join('users','leadspeek_users.user_id','=','users.id')
                                 ->where('leadspeek_users.id','=',$leadspeek_apiID)
                                 ->get();
        /* GET LEADSPEEK USER */

        if (count($leaduser) > 0) {
            $epochTime = Carbon::now()->timestamp;
            $numberRandom = $this->generateUniqueNumber();

            /* CREATE JOB PROGRESS */
            $jobProgress = JobProgress::create([
                'job_id' => $numberRandom,
                'upload_at' => $epochTime,
                'lead_userid' => $leadspeek_apiID,
                'company_id' => $leaduser[0]['company_id'],
                'suppression_type' => $campaigntype,
                'leadspeek_api_id' => $leaduser[0]['leadspeek_api_id'],
                'filename' => $filenameOri,
                'status' => 'queue',
            ]);
            /* CREATE JOB PROGRESS */

            /* RUNNING QUEUE */
            ChunkCsvJob::dispatch($numberRandom, [
                'filedownload_url' => $filedownload_url,
                'path' => $path,
                'upload_at' => $epochTime,
                'filename' => $filenameOri,
                'lead_userid' => $leadspeek_apiID,
                'leadspeek_api_id' => $leaduser[0]['leadspeek_api_id'],
                'company_id' => $leaduser[0]['company_id'],
                'suppression_type' => $campaigntype,
            ]);
            /* RUNNING QUEUE */
        }

        return response()->json([
            'result' => 'success',
            'filename' => $filedownload_url,
        ]);
    }

    public function ClientOptout(Request $request) 
    {
        $ClientCompanyID = $request->ClientCompanyID;

        /* CONFIGURATION FOR SPACES */
        $filenameOri = $request->file('clientoptoutfile')->getClientOriginalName();
        $filename = pathinfo($filenameOri, PATHINFO_FILENAME);
        $extension = $request->file('clientoptoutfile')->getClientOriginalExtension();
        $tmpfile = Carbon::now()->valueOf() . '_company_' . $ClientCompanyID . '.' . $extension; 
        /* CONFIGURATION FOR SPACES */

        /* UPLOAD TO DO SPACES */
        $path = Storage::disk('spaces')->putFileAs('tools/optout', $request->file('clientoptoutfile'), $tmpfile);
        $filedownload_url = Storage::disk('spaces')->url($path);
        $filedownload_url = str_replace('digitaloceanspaces', 'cdn.digitaloceanspaces', $filedownload_url);
        /* UPLOAD TO DO SPACES */

        $epochTime = Carbon::now()->timestamp;
        $numberRandom = $this->generateUniqueNumber();
        
        /* JOBPROGRESS */
        $jobProgress = JobProgress::create([
                'job_id' => $numberRandom,
                'upload_at' => $epochTime,
                'lead_userid' => '',
                'company_id' => $ClientCompanyID,
                'suppression_type' => 'client',
                'leadspeek_api_id' => '',
                'filename' => $filenameOri,
                'status' => 'queue',
            ]);
        /* JOBPROGRESS */

        /* RUNNING QUEUE */
        ChunkCsvClientJob::dispatch($numberRandom, [
            'upload_at' => $epochTime,
            'filedownload_url' => $filedownload_url,
            'path' => $path,
            'company_id' => $ClientCompanyID,
            'suppression_type' => 'client',
            'filename' => $filenameOri,
        ]);
        /* RUNNING QUEUE */

        return response()->json([
            'result' => 'success',
            'filename' => $filedownload_url,
        ]);
    }

    public function optout(Request $request)
    {
        $companyRootId = (isset($request->companyRootId))?$request->companyRootId:'';

        /* CONFIGURATION FOR SPACES */
        $filenameOri = $request->file('optoutfile')->getClientOriginalName();
        $filename = pathinfo($filenameOri, PATHINFO_FILENAME);
        $extension = $request->file('optoutfile')->getClientOriginalExtension();
        $tmpfile = Carbon::now()->valueOf() . 'optoutlist.' . $extension;
        /* CONFIGURATION FOR SPACES */

        /* UPLOAD TO DO SPACES */
        $path = Storage::disk('spaces')->putFileAs('tools/optout', $request->file('optoutfile'), $tmpfile);
        $filedownload_url = Storage::disk('spaces')->url($path);
        $filedownload_url = str_replace('digitaloceanspaces', 'cdn.digitaloceanspaces', $filedownload_url);
        /* UPLOAD TO DO SPACES */

        $epochTime = Carbon::now()->timestamp;
        $numberRandom = $this->generateUniqueNumber();

        /* JOBPROGRESS */
        $jobProgress = JobProgress::create([
            'job_id' => $numberRandom,
            'upload_at' => $epochTime,
            'lead_userid' => '',
            'company_id' => $companyRootId,
            'suppression_type' => '',
            'leadspeek_api_id' => '',
            'filename' => $filenameOri,
            'status' => 'queue',
        ]);
        /* JOBPROGRESS */

        /* RUNNING QUEUE */
        ChunCsvOptoutJob::dispatch($numberRandom, [
            'upload_at' => $epochTime,
            'filedownload_url' => $filedownload_url,
            'path' => $path,
            'filename' => $filenameOri,
            'company_root_id' => $companyRootId,
        ]);
        /* RUNNING QUEUE */

        return response()->json([
            'result' => 'success',
            'filename' => $filedownload_url,
        ]);
    }

    public function generateUniqueNumber()
    {
        do {
            $number = mt_rand(1, 1000000000);
        } while (JobProgress::where('job_id')->exists());

        return $number;
    }
}
