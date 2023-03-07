<?php

use Carbon\Carbon;

/*
 * 25th October 2022, 2:10:06 pm format
 *
 * */
if (! function_exists('dateMonthYearTimeFormat')) {

    function dateMonthYearTimeFormat($dateTime): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $dateTime)->isoFormat('Do MMM YYYY, h:mm:ss a');
        //return Carbon::parse($dateTime)->diffForHumans();
    }

}

/*
 * 25th Oct 2022 date format
 *
 * */
if (! function_exists('dateMonthYearFormat')) {

    function dateMonthYearFormat($dateTime): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $dateTime)->format('d M,Y');
        //return Carbon::parse($dateTime)->diffForHumans();
    }

}


/*
 * 2:10:06 pm time format
 *
 * */
if (! function_exists('timeFormat')) {

    function timeFormat($dateTime): string
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $dateTime)->isoFormat('h:mm A');
        //return Carbon::parse($dateTime)->diffForHumans();
    }

}

/*
 * date to 25th Oct 2022 time format
 *
 * */
if (! function_exists('dateToDateFormat')) {

    function dateToDateFormat($dateTime): string
    {
        return Carbon::createFromFormat('Y-m-d', $dateTime)->format('d M,Y');
        //return Carbon::parse($dateTime)->diffForHumans();
    }

}

/*
 * time to 2:10:06 pm time format
 *
 * */
if (! function_exists('timeToTimeFormat')) {

    function timeToTimeFormat($dateTime): string
    {
        return Carbon::createFromFormat('H:i:s', $dateTime)->isoFormat('h:mm A');
        //return Carbon::parse($dateTime)->diffForHumans();
    }

}


