<?php


namespace App\Services;
use App\Models\Gamereport;

class GamereportService
{
    public function add($data){

        $addtime = strtotime(date('Y-m-d'.' H:i:s'));
        $info = Gamereport::where("uid",$data['uid'])->where('addtime',$addtime)->first();
        if($info){
            if(isset($data['recnum'])){
                $info->recnum += 1;
            }
            if(isset($data['totalrechange'])){
                $info->rechangenum += 1;
                $info->totalrechange += $data['totalrechange'];
            }
            if(isset($data['totalwithdraw'])){
                $info->totalrechange += 1;
                $info->totalrechange += $data['totalwithdraw'];
            }
            if(isset($data['totalbet'])){
                $info->betnum += 1 ;
                $info->totalrechange += $data['totalbet'];
                $info->valid_amount += $data['valid_amount'];

            }
            if(isset($data['totalwinloss'])){
                $info->totalrechange += $data['totalwinloss'];
            }
            if(isset($data['totalredpackect'])){
                $info->redpackectnum += 1;
                $info->totalrechange += $data['totalredpackect'];
            }
            if(isset($data['releasewater'])){
                $info->totalrechange += $data['releasewater'];
                $info->releasewaternum += 1;
            }
            if(isset($data['rakeback'])){
                $info->totalrechange += $data['rakeback'];
                $info->rakebacknum += 1;
            }
            $info->save();
        }else{
            $data['addtime'] = strtotime(date('Y-m-d'.' H:i:s'));

            if(isset($data['totalrechange'])){
                $data['rechangenum']=1;
            }
            if(isset($data['recnum'])){
                $data['recnum']=1;
            }
            if(isset($data['totalwithdraw'])){
                 $data['withdrawnum'] =1;
            }
            if(isset($data['totalbet'])){
                $data['betnum'] = 1 ;
            }

            if(isset($data['totalredpackect'])){
               $data['redpackectnum'] =1;
            }

            if(isset($data['releasewater'])){
                $data['releasewaternum'] = 1;
            }

            if(isset($data['rakeback'])){
                $data['rakebacknum'] = 1;
            }
            unset($data['handle']);
            Gamereport::create($data);

        }


    }

}
