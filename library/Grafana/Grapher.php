<?php

namespace Icinga\Module\Grafana;

use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;
use Icinga\Web\Hook\GrapherHook;
use Icinga\Web\Url;

class Grapher extends GrapherHook
{
    protected $auth;
    protected $grafana = array();
    protected $hasPreviews = true;
    protected $hasTinyPreviews = true;
    protected $height = 280;
    protected $host = "grafana.example.com";
    protected $parametrized = array();
    protected $password = null;
    protected $protocol = "https";
    protected $username = null;
    protected $width = 640;

    protected function init()
    {
        $cfg = Config::module('grafana')->getSection('grafana');
        $this->username = $cfg->get('username', $this->username);
        $this->height = $cfg->get('height', $this->height);
        $this->host = $cfg->get('host', $this->host);
        $this->parametrized = array_map('trim', explode(",", $cfg->get('parametrized', $this->parametrized)));
        $this->password = $cfg->get('password', $this->password);
        $this->protocol = $cfg->get('protocol', $this->protocol);
        $this->width = $cfg->get('width', $this->width);
        if($this->username != null){
            if($this->password != null){
                $this->auth = $this->username.":".$this->password."@";
            } else {
                $this->auth = $this->username."@";
            }
        } else {
            $this->auth = "";
        }
        $graphs = array_map('trim', explode(",", $cfg->get('graphs', array())));
        foreach ($graphs as $graph) {
            $panel = $cfg->get("graphs.$graph.panel", null);
            $dashboard = $cfg->get("graphs.$graph.dashboard", null);
            if ($panel !== null && $dashboard !== null) {
                $this->grafana[$graph] = array("dashboard" => $dashboard, "panelId" => $panel);
            }
        }
    }

    private function getPreviewImage($service, $metric)
    {
        $hostname = $service->host->getName();
        $serviceName = $service->getName();
        $serviceParameter = "";
        $pos = strpos($serviceName, " ");
        if ($pos !== false) {
            $p1 = substr($serviceName, 0, $pos);
            if (in_array($p1, $this->parametrized)) {
                $parameter = substr($serviceName, $pos+1);
                $serviceName = $p1;
                $serviceParameter = "&var-".$serviceName."=".urlencode($parameter);
            }
        }
        $dashboard = $this->grafana[$serviceName]["dashboard"];
        $panelId = $this->grafana[$serviceName]["panelId"];
        $auth = $this->auth;
        $height = $this->height;
        $host = $this->host;
        $proto = $this->protocol;
        $width = $this->width;
        $to = time()*1000;
        $from = $to - 60*60*24*1000;
        $pngUrl = "$proto://$auth$host/render/dashboard-solo/db/$dashboard?var-hostname=$hostname$serviceParameter&panelId=$panelId&width=$width&height=$height&theme=light&from=$from&to=$to";

        $ctx = stream_context_create(array('ssl' => array("verify_peer"=>true, "verify_peer_name"=>true), 'http' => array('method' => 'GET', 'timeout' => 5)));
        $imgBinary = @file_get_contents($pngUrl, false, $ctx);
        $error = error_get_last();
        if ($error !== null) {
            return "Graph currently unavailable: ".$error["message"];
        }

        $img = 'data:image/png;base64,'.base64_encode($imgBinary);
        $html = '<img src="%s" alt="%s" width="%d" height="%d" />';
        return sprintf(
            $html,
            $img,
            $metric,
            $this->width,
            $this->height
      );
    }

    public function has(MonitoredObject $object)
    {
        if (($object instanceof Host)||($object instanceof Service)) {
            return true;
        } else {
            return false;
        }
    }

    public function getPreviewHtml(MonitoredObject $object)
    {
        $object->fetchCustomvars();

        if (array_key_exists("grafana", $object->customvars)) {
            $this->parseGrapherConfig($object->customvars["grafana"]);
        }
        
        if ($object instanceof Service) {
            $serviceName = $object->getName();
            $pos = strpos($serviceName, " ");
            if ($pos !== false) {
                    $serviceName = substr($serviceName, 0, $pos);
            }
            if (array_key_exists($serviceName, $this->grafana)) {
                    $service = $object;
            } else {
                return '';
            }
        } else {
            return '';
        }

        $grafana_host = $this->host;
        $proto = $this->protocol;
        $hostname = $service->host->getName();
        $dashboard = $this->grafana[$serviceName]["dashboard"];
        $html = "";
        $html .= $this->getPreviewImage($service, "");
        $html .= '<br />';
        $html .= "<a href=\"$proto://$grafana_host/dashboard/db/$dashboard?var-hostname=".$hostname."\" target=\"_blank\">View in Grafana</a>";
        return $html;
    }
}
