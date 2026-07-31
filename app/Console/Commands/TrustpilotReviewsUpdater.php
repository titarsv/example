<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TrustpilotReviewsUpdater extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'update_trustpilot_reviews';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update trustpilot reviews';

    public function getHeaders(){
        $api_key = 'ZTVmYzQ1OWUxOWMxNGE3ZDg5Y2ZkNzcwNWUzYjlhMDd8MWFlZDc4NDQ4MA';
        $headers = array();
        $headers[] = "Accept: application/json";
        $headers[] = "Client: PHP SDK 3.2.0";
        $headers[] = "X-API-KEY: {$api_key}";

        return $headers;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $params = http_build_query(array(
            "query" => ["https://www.trustpilot.com/review/proper-loud.uk"],
            "language" => "all",
            "org" => "os",
            "service_name" => "trustpilot_reviews_service",
            "est" => 10,
            "limit_per_query" => 10,
            "sort" => "recency",
            "stars" => [],
            "tags" => "",
            "input_file" => null,
            "queries_amount" => 1,
            "limit" => 0,
            "enrich" => false,
            "enrichments" => []
        ));

        $result = $this->make_get_request("trustpilot/reviews?{$params}");
        $reviews = $this->wait_request_archive($result["id"]);

        dd($reviews);
    }

    private function make_get_request($url){
        $url = preg_replace('/%5B[0-9]+%5D/simU', '', $url);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://api.outscraper.cloud/{$url}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->getHeaders());

        $result = json_decode(curl_exec($ch), true);
        curl_close($ch);

        return $result;
    }

    private function wait_request_archive($request_id){
        $ttl = 3600 / 5;

        while($ttl > 0){
            $ttl--;
            sleep(5);

            $result = $this->get_request_archive($request_id);
            if ($result["status"] != "Pending") {
                return $result;
            }
        }

        return null;
    }

    public function get_request_archive($request_id){
        if($request_id == NULL)
            return null;

        return $this->make_get_request("requests/{$request_id}");
    }
}
