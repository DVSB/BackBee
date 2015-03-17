<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Rest\Controller;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * REST API for Resources
 *
 * @category    BackBee
 * @package     BackBee\Rest
 * @copyright   Lp digital system
 * @author      f.kroockmann <florian.kroockmann@lp-digital.fr>
 */
class ResourceController extends ARestController
{
    public function uploadAction() 
    {
        $request = $this->getRequest();
        $application = $this->getApplication();
        
        $tmpDirectory = $application->getTemporaryDir();
        $files = $request->files;
        $data = [];
        
        if ($files->count() === 1) {
            foreach ($files as $file) {
                if (null !== $file) {
                    if ($file->isValid()) {
                        if ($file->getClientSize() <= $file->getMaxFilesize()) {
                            $data['originalname'] = $file->getClientOriginalName();
                            
                            $fileName = md5($file->getClientOriginalName() . time() . $file->getClientSize()) . '.' . $file->guessExtension();
                            $data['path'] = $tmpDirectory . DIRECTORY_SEPARATOR . $fileName;

                            $file->move($tmpDirectory, $fileName);
                        } else {
                            throw new BadRequestHttpException('Too big file, the max file size is ' . $file->getMaxFilesize());
                        }
                    } else {
                        throw new BadRequestHttpException($file->getErrorMessage());
                    }
                }

                break;
            }
        } else {
            if ($files->count() === 0) {
                throw new NotFoundHttpException('No file to upload');
            } else {
                throw new BadRequestHttpException('You can upload only one file by request');
            }
        }
        
        return $this->createJsonResponse($data, 201);
    }
}
