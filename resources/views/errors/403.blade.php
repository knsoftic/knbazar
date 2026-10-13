@extends('errors.layout')

@section('code', '403')
@section('title', 'Access denied')
@section('message', $exception?->getMessage() && $exception->getMessage() !== 'This action is unauthorized.' ? $exception->getMessage() : 'You do not have permission to access this page.')
