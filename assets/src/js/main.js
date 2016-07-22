import FrontEnd from "./controllers/frontend.js";
import Dashboard from "./controllers/dashboard.js";

jQuery(document).ready(function(){
    "use strict";
    if(wbFIData.isAdmin){
        new Dashboard();
    }else{
        new FrontEnd();
    }
});