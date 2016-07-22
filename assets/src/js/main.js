import FrontEnd from "./controllers/frontend.js";
import Dashboard from "./controllers/dashboard.js";

jQuery(document).ready(function(){
    "use strict";
    if(wbgData.isAdmin){
        new Dashboard();
    }else{
        new FrontEnd();
    }
});