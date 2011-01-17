<?php

class IWtimeframes_Controller_Admin extends Zikula_Controller {
    public function main() {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        $view = Zikula_View::getInstance('IWtimeframes', false);
        $frames = array();
        //Cridem la funció API anomenada getall i que retornarï¿œ la informació
        $frames = ModUtil::apiFunc('IWtimeframes', 'user', 'getall');

        //Per si no hi ha marcs definits
        $hihamarcs = (empty($frames)) ? false : true;

        $view->assign('hi_ha_marcs', $hihamarcs);
        $view->assign('marcs', $frames);
        return $view->fetch('IWtimeframes_admin_main.htm');
    }

    public function module() {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'), 403);
        }

        // Create output object
        $view = Zikula_View::getInstance('IWtimeframes', false);
        $module = ModUtil::func('IWmain', 'user', 'module_info',
                                 array('module_name' => 'IWtimeframes',
                                       'type' => 'admin'));
        $view->assign('module', $module);

        return $view->fetch('IWtimeframes_admin_module.htm');
    }

    /*
      funció que presenta el formulari des d'on es demanen la dades del nou marc que es vol crear
     */

    public function newItem() {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');
        $m = FormUtil::getPassedValue('m', isset($args['m']) ? $args['m'] : null, 'GET');

        $view = Zikula_View::getInstance('IWtimeframes', false);

        $nom_marc = '';
        $descriu = '';

        if (!empty($mdid)) {
            //Agafem les dades del registre a editar
            $registre = ModUtil::apiFunc('IWtimeframes', 'user', 'get',
                                          array('mdid' => $mdid));
            if (empty($registre)) {
                return LogUtil::registerError($this->__('Error! Could not load module.'));
            }

            //posem els valors dels camps
            $nom_marc = $registre['nom_marc'];
            $descriu = $registre['descriu'];
        }

        switch ($m) {
            case 'n': //new
                $accio = $this->__('Add new timeFrame');
                $acciosubmit = $this->__('Creates the timeFrame');
                break;
            case 'e': //edit
                $accio = $this->__('Edit the TimeFrame');
                $acciosubmit = $this->__('Change');
                break;
        }

        $view->assign('nom_marc', $nom_marc);
        $view->assign('m', $m);
        $view->assign('mdid', $mdid);
        $view->assign('descriu', $descriu);
        $view->assign('acciosubmit', $acciosubmit);
        $view->assign('accio', $accio);

        return $view->fetch('IWtimeframes_admin_newItem.htm');
    }

    /*
      funció que comprova que les dades enviades des del formulari de creaciï¿œ d'un
      nou marc horari s'ajusten al que ha de ser i envia l'ordre de crear el registre
     */

    public function create($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'POST');
        $m = FormUtil::getPassedValue('m', isset($args['m']) ? $args['m'] : null, 'POST');
        $nom_marc = FormUtil::getPassedValue('nom_marc', isset($args['nom_marc']) ? $args['nom_marc'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');

        //confirmació del codi d'autoritzaciï¿œ.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWtimeframes', 'admin', 'main'));
        }

        if ($m == 'n') {
            //Es crida la funció API amb les dades extretes del formulari
            if (ModUtil::apiFunc('IWtimeframes', 'admin', 'create',
                                  array('nom_marc' => $nom_marc,
                                        'descriu' => $descriu))) {
                //success
                LogUtil::registerStatus($this->__('We have created a new timeFrame.'));
            }
        } else {
            if (ModUtil::apiFunc('IWtimeframes', 'admin', 'update',
                                  array('nom_marc' => $nom_marc,
                                        'descriu' => $descriu,
                                        'mdid' => $mdid))) {
                // Success
                LogUtil::registerStatus($this->__('timeFrame was updated'));
            }
        }
        return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'main'));
    }

    /*
      funció que gestiona l'esborrament d'un marc horari i envia les dades a la funció API corresponent per fer l'ordre efectiva
     */

    public function delete($args) {
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'REQUEST');
        $confirmacio = FormUtil::getPassedValue('confirmacio', isset($args['confirmacio']) ? $args['confirmacio'] : null, 'POST');
        $mode = FormUtil::getPassedValue('m', isset($args['m']) ? $args['m'] : null, 'POST');
        $referenced = FormUtil::getPassedValue('r', isset($args['r']) ? $args['r'] : null, 'REQUEST');

        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        //Cridem la funció de l'API de l'usuari que ens retornarï¿œ la informació del registre demanat
        $registre = ModUtil::apiFunc('IWtimeframes', 'user', 'get',
                        array('mdid' => $mdid));
        $view = Zikula_View::getInstance('IWtimeframes', false);

        if (empty($registre)) {
            return LogUtil::registerError($this->__('Can not find the timeFrame over which do the action.') . " mdid - " . $mdid);
        }

        //Comprovaciï¿œ de seguretat
        if (!SecurityUtil::checkPermission('IWtimeframes::Item', "$registre[nom_marc]::$mdid", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'));
        }
        //Demanem confirmació per l'esborrament del registre, si no s'ha demanat abans
        if (empty($confirmacio) and empty($referenced)) {
            $view->assign('mdid', $mdid);
            $view->assign('nom_marc', $registre['nom_marc']);
            return $view->fetch('IWtimeframes_admin_del.htm');
        }

        //L'usuari ha confirmat l'esborrament del registre i procedim a fer-ho efectiu
        // Check if frame is referenced in bookings module
        if (empty($referenced)) {
            $referenced = ModUtil::apiFunc('IWtimeframes', 'admin', 'referenced',
                                            array('mdid' => $mdid));
            if ($referenced) {
                $view->assign('referenced', $referenced);
                $view->assign('mdid', $mdid);
                $view->assign('nom_marc', $registre['nom_marc']);
                return $view->fetch('IWtimeframes_admin_del.htm');
            }
        }
        //confirmació del codi de seguretat
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWtimeframes', 'admin', 'main'));
        }

        //Cridem la funció API que farï¿œ l'esborrament del registre
        if (ModUtil::apiFunc('IWtimeframes', 'admin', 'delete',
                              array('mdid' => $mdid,
                                    'm' => $mode))) {
            //L'esborrament ha estat un ï¿œxit i ho notifiquem
            SessionUtil::setVar('statusmsg', $this->__('Has been deleted the timeFrame'));
        }

        //Enviem a l'usuari a la taula de marcs horari
        return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'main'));
    }

    /* ---------------------------------------------------------------------------------------------------------*\
     * 												HORES 														*
      \* --------------------------------------------------------------------------------------------------------- */

    /*
      Funció que mostra la informació d'un marc horari
     */

    public function timetable() {
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');

        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        //Cridem la funció de l'API de l'usuari que ens retornarà la informació del registre demanat
        $item = ModUtil::apiFunc('IWtimeframes', 'user', 'get',
                                  array('mdid' => $mdid));

        $horari = ModUtil::apiFunc('IWtimeframes', 'user', 'getall_horari',
                                    array('mdid' => $mdid));
        !empty($horari) ? $hi_ha_hores = true : $hi_ha_hores = false;
        $hasbookings = ModUtil::apiFunc('IWtimeframes', 'admin', 'hasbookings',
                                         array('mdid' => $mdid));

        $view = Zikula_View::getInstance('IWtimeframes', false);
        $view->assign('nom_marc', $item['nom_marc']);
        $view->assign('horari', $horari);
        $view->assign('hi_ha_hores', $hi_ha_hores);
        $view->assign('hasbookings', $hasbookings);
        $view->assign('mdid', $mdid);

        return $view->fetch('IWtimeframes_admin_timetables.htm');
    }

    /*
      funció que presenta el formulari des d'on es demanen la dades per introduir una nova hora en el marc horari
     */

    public function new_hour($args) {
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');
        $mode = FormUtil::getPassedValue('m', isset($args['m']) ? $args['m'] : null, 'GET');
        $hid = FormUtil::getPassedValue('hid', isset($args['hid']) ? $args['hid'] : null, 'GET');

        // Mirar si existeixen reserves que facin referï¿œncia a aquest marc horari
        if (ModUtil::apiFunc('IWtimeframes', 'admin', 'hasbookings',
                              array('mdid' => $mdid))) {
            return LogUtil::registerError($this->__('Operation unavailable. There are reservations on this time frame.'));
        }

        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADD)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        //Contruim les matrius d'hores i minuts
        $hora = array(array('id' => '08', 'name' => '08'),
                      array('id' => '09', 'name' => '09'),
                      array('id' => '10', 'name' => '10'),
                      array('id' => '11', 'name' => '11'),
                      array('id' => '12', 'name' => '12'),
                      array('id' => '13', 'name' => '13'),
                      array('id' => '14', 'name' => '14'),
                      array('id' => '15', 'name' => '15'),
                      array('id' => '16', 'name' => '16'),
                      array('id' => '17', 'name' => '17'),
                      array('id' => '18', 'name' => '18'),
                      array('id' => '19', 'name' => '19'),
                      array('id' => '20', 'name' => '20'),
                      array('id' => '21', 'name' => '21'),
                      array('id' => '22', 'name' => '22'));

        $minut = array(array('id' => '00', 'name' => '00'),
                       array('id' => '05', 'name' => '05'),
                       array('id' => '10', 'name' => '10'),
                       array('id' => '15', 'name' => '15'),
                       array('id' => '20', 'name' => '20'),
                       array('id' => '25', 'name' => '25'),
                       array('id' => '30', 'name' => '30'),
                       array('id' => '35', 'name' => '35'),
                       array('id' => '40', 'name' => '40'),
                       array('id' => '45', 'name' => '45'),
                       array('id' => '50', 'name' => '50'),
                       array('id' => '55', 'name' => '55'));
        $view = Zikula_View::getInstance('IWtimeframes', false);
        $nova_hora = false;
        $editmode = false;
        $descriu = '';
        switch ($mode) {
            case 'n': //new
                $accio = $this->__('New time');
                $acciosubmit = $this->__('Add time at timeFrame ');
                $nova_hora = true;
                break;
            case 'e': //edit
                $accio = $this->__('Edit the TimeFrame');
                $acciosubmit = $this->__('Change');
                $editmode = true;
                $period = ModUtil::apiFunc('IWtimeframes', 'user', 'get_hour',
                                            array('hid' => $hid));
                if ($period == false) {
                    return LogUtil::registerError($this->__('Can not find the timeFrame over which do the action.'));
                }
                $view->assign('starth', date('H', strtotime($period['start'])));
                $view->assign('startm', date('i', strtotime($period['start'])));
                $view->assign('endh', date('H', strtotime($period['end'])));
                $view->assign('endm', date('i', strtotime($period['end'])));
                $descriu = $period['descriu'];
                $view->assign('hid', $hid);
                break;
                return logUtil::registerError($this->__('Error! Could not load module.'));
        }
        $horari = ModUtil::apiFunc('IWtimeframes', 'user', 'getall_horari',
                                    array('mdid' => $mdid));
        !empty($horari) ? $hi_ha_hores = true : $hi_ha_hores = false;
        $item = ModUtil::apiFunc('IWtimeframes', 'user', 'get',
                                  array('mdid' => $mdid));
        $view->assign('descriu', $descriu);
        $view->assign('accio', $accio);
        $view->assign('acciosubmit', $acciosubmit);
        $view->assign('nom_marc', $item['nom_marc']);
        $view->assign('hores', $hora);
        $view->assign('minuts', $minut);
        $view->assign('hi_ha_hores', $hi_ha_hores);
        $view->assign('horari', $horari);
        $view->assign('mdid', $mdid);
        $view->assign('nova_hora', $nova_hora);
        $view->assign('editmode', $editmode);

        return $view->fetch('IWtimeframes_admin_timetables.htm');
    }

    /*
      funció que comprova que les dades enviades des del formulari de creaciï¿œ d'una
      nova hora per un marc horari s'ajusten al que ha de ser i envia l'ordre
      de crear el registre a la funció new_hora de l'API
     */

    public function create_hour($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADD)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        // Mirar si existeixen reserves que facin referï¿œncia a aquest marc horari
        if (ModUtil::apiFunc('IWtimeframes', 'admin', 'hasbookings',
                              array('mdid' => $mdid))) {
            return LogUtil::registerError($this->__('Operation unavailable. There are reservations on this time frame.'));
        }

        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'POST');
        $hora_i = FormUtil::getPassedValue('hora_i', isset($args['hora_i']) ? $args['hora_i'] : null, 'POST');
        $hora_f = FormUtil::getPassedValue('hora_f', isset($args['hora_f']) ? $args['hora_f'] : null, 'POST');
        $minut_i = FormUtil::getPassedValue('minut_i', isset($args['minut_i']) ? $args['minut_i'] : null, 'POST');
        $minut_f = FormUtil::getPassedValue('minut_f', isset($args['minut_f']) ? $args['minut_f'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');


        //confirmació del codi d'autoritzaciï¿œ.
        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWtimeframes', 'admin', 'main'));
        }

        //Construim la franja horï¿œria i comprovem que l'hora inicial sigui mï¿œs petita que la hora final
        $hora_inicial = $hora_i . ':' . $minut_i;
        $hora_final = $hora_f . ':' . $minut_f;

        if ($hora_inicial >= $hora_final) {
            LogUtil::registerError($this->__('The time allocated is not correct.'));
            return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'new_hour',
                                                  array('mdid' => $mdid,
                                                        'm' => 'n')));
        }

        // Check for overlaping time periods
        $overlap = ModUtil::apiFunc('IWtimeframes', 'admin', 'overlap',
                                     array('mdid' => $mdid,
                                           'start' => $hora_inicial,
                                           'end' => $hora_final));
        if ($overlap) {
            LogUtil::registerError($this->__('Warning! The new time is overlaps with some of the existing ones.'));
        }

        //Insert new time into DB
        $lid = ModUtil::apiFunc('IWtimeframes', 'admin', 'create_hour',
                                 array('mdid' => $mdid,
                                       'start' => $hora_inicial,
                                       'end' => $hora_final,
                                       'descriu' => $descriu));

        if ($lid != false) {
            //S'ha creat una nova hora dins del marc horari
            SessionUtil::setVar('statusmsg', $this->__('Have created a new time in timeFrame'));
        }
        $horari = ModUtil::apiFunc('IWtimeframes', 'user', 'getall_horari',
                                    array('mdid' => $mdid));
        !empty($horari) ? $hi_ha_hores = true : $hi_ha_hores = false;
        return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'timetable',
                                              array('mdid' => $mdid)));
    }

    /*
      funció que presenta el formulari que ens mostra l'horari i informació de l'hora que es vol esborrar
     */

    public function delete_hour($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        // Mirar si existeixen reserves que facin referï¿œncia a aquest marc horari
        if (ModUtil::apiFunc('IWtimeframes', 'admin', 'hasbookings',
                              array('mdid' => $mdid))) {
            return LogUtil::registerError($this->__('Operation unavailable. There are reservations on this time frame.'));
        }

        $hid = FormUtil::getPassedValue('hid', isset($args['hid']) ? $args['hid'] : null, 'GET');
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');
        $confirmacio = FormUtil::getPassedValue('c', isset($args['c']) ? $args['c'] : null, 'GET');

        //Cridem la funció de l'API de l'usuari que ens retornarï¿œ la informació del registre demanat

        $theHour = ModUtil::apiFunc('IWtimeframes', 'user', 'get_hour',
                                     array('hid' => $hid));

        if ($theHour == false) {
            return LogUtil::registerError($this->__('Can not find the timeFrame over which do the action.'));
        }

        //Comprovaciï¿œ de seguretat
        if (!SecurityUtil::checkPermission('IWtimeframes::Item', "$registre[nom_marc]::$mdid", ACCESS_DELETE)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'));
        }

        //Demanem confirmació per l'esborrament del registre, si no s'ha demanat abans
        if (empty($confirmacio)) {
            $horari = ModUtil::apiFunc('IWtimeframes', 'user', 'getall_horari',
                                        array('mdid' => $mdid));
            $item = ModUtil::apiFunc('IWtimeframes', 'user', 'get',
                                      array('mdid' => $mdid));

            $view = Zikula_View::getInstance('IWtimeframes', false);

            $view->assign('nom_marc', $item['nom_marc']);
            $view->assign('start', date('H:i', strtotime($theHour['start'])));
            $view->assign('end', date('H:i', strtotime($theHour['end'])));
            $view->assign('horari', $horari);
            $view->assign('mdid', $mdid);
            $view->assign('hid', $hid);

            return $view->fetch('IWtimeframes_admin_deletehour.htm');
        }
        //L'usuari ha confirmat l'esborrament del registre i procedim a fer-ho efectiu
        //Cridem la funció API que farà l'esborrament del registre
        if (ModUtil::apiFunc('IWtimeframes', 'admin', 'delete_hour',
                              array('hid' => $hid))) {
            //L'esborrament ha estat un ï¿œxit i ho notifiquem
            SessionUtil::setVar('statusmsg', $this->__('Was deleted the time in timeFrame'));
        }

        //Enviem a l'usuari a la taula amb les hores del marc horari
        return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'timetable',
                                              array('mdid' => $mdid)));
    }

    /*
      funció que presenta el formulari que ens mostra i permet editar les dades d'una hora que es vol modificar
     */

    public function edit_hour($args) {
        $hid = FormUtil::getPassedValue('hid', isset($args['hid']) ? $args['hid'] : null, 'GET');
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'GET');

        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_ADMIN)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }

        // Mirar si existeixen reserves que facin referï¿œncia a aquest marc horari
        if (ModUtil::apiFunc('IWtimeframes', 'admin', 'hasbookings',
                              array('mdid' => $mdid))) {
            return LogUtil::registerError($this->__('Operation unavailable. There are reservations on this time frame.'));
        }


        $period = ModUtil::apiFunc('IWtimeframes', 'user', 'get_hour',
                                    array('hid' => $hid));
        if ($period == false) {
            return LogUtil::registerError($this->__('Can not find the timeFrame over which do the action.'));
        }

        return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'new_hour',
                                              array('mdid' => $mdid,
                                                    'hid' => $hid,
                                                    'm' => 'e')));
    }

    /*
      funció que comprova que les dades enviades des del formulari de modificaciï¿œ d'una
      hora per un marc horari s'ajusten al que ha de ser i envia l'ordre d'actualitzar el registre
     */

    public function update_hour($args) {
        // Security check
        if (!SecurityUtil::checkPermission('IWtimeframes::', "::", ACCESS_EDIT)) {
            return LogUtil::registerError($this->__('Not authorized to manage timeFrames.'), 403);
        }
        $hid = FormUtil::getPassedValue('hid', isset($args['hid']) ? $args['hid'] : null, 'POST');
        $mdid = FormUtil::getPassedValue('mdid', isset($args['mdid']) ? $args['mdid'] : null, 'POST');
        $hora_i = FormUtil::getPassedValue('hora_i', isset($args['hora_i']) ? $args['hora_i'] : null, 'POST');
        $hora_f = FormUtil::getPassedValue('hora_f', isset($args['hora_f']) ? $args['hora_f'] : null, 'POST');
        $minut_i = FormUtil::getPassedValue('minut_i', isset($args['minut_i']) ? $args['minut_i'] : null, 'POST');
        $minut_f = FormUtil::getPassedValue('minut_f', isset($args['minut_f']) ? $args['minut_f'] : null, 'POST');
        $descriu = FormUtil::getPassedValue('descriu', isset($args['descriu']) ? $args['descriu'] : null, 'POST');

        if (!SecurityUtil::confirmAuthKey()) {
            return LogUtil::registerAuthidError(ModUtil::url('IWtimeframes', 'admin', 'horari',
                                                              array('mdid' => $mdid)));
        }

        //Construim la franja horï¿œria i comprovem que l'hora inicial sigui mï¿œs petita que la hora final
        $start = $hora_i . ':' . $minut_i;
        $end = $hora_f . ':' . $minut_f;

        if ($start >= $end) {
            LogUtil::registerError($this->__('The time allocated is not correct.'));
            return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'timetable',
                                                  array('mdid' => $mdid)));
        }

        // Check for overlaping time periods
        $overlap = ModUtil::apiFunc('IWtimeframes', 'admin', 'overlap',
                                     array('mdid' => $mdid,
                                           'start' => $start,
                                           'end' => $end,
                                           'hid' => $hid));
        if ($overlap) {
            LogUtil::registerError($this->__('Warning! The new time is overlaps with some of the existing ones.'));
        }

        //Insert new time into DB
        $lid = ModUtil::apiFunc('IWtimeframes', 'admin', 'update_hour',
                        array('mdid' => $mdid,
                              'start' => $start,
                              'end' => $end,
                              'descriu' => $descriu,
                              'hid' => $hid));

        if (!empty($lid)) {
            //S'ha creat una nova hora dins del marc horari
            SessionUtil::setVar('statusmsg', $this->__('Has changed the time.'));
        }
        $horari = ModUtil::apiFunc('IWtimeframes', 'user', 'getall_horari',
                                    array('mdid' => $mdid));

        return System::redirect(ModUtil::url('IWtimeframes', 'admin', 'timetable',
                                              array('mdid' => $mdid)));
    }
}