@extends('layouts.app')

@section('content')
    <div class="container">
        <h1 class="text-center mt-3">勤怠詳細</h1>

        <form method="POST" action="{{ route('attendance.detail', $attendance->id) }}">
            @csrf
            @method('POST')

            <!-- 名前 -->
            <div class="form-group row mt-3">
                <label for="name" class="col-sm-2 col-form-label">名前</label>
                <div class="col-sm-10">
                    <input type="text" readonly class="form-control-plaintext" id="name" value="{{ $user->name }}">
                </div>
            </div>

            <!-- 日付 -->
            <div class="form-group row mt-3">
                <label for="date" class="col-sm-2 col-form-label">日付</label>
                <div class="col-sm-10">
                    <input type="date" class="form-control @error('date') is-invalid @enderror" id="date"
                        name="date" value="{{ old('date', $attendance->date) }}"
                        {{ $hasPendingCorrection ? 'disabled' : '' }}>
                    @error('date')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- 出勤・退勤 -->
            <div class="form-group row mt-3">
                <label class="col-sm-2 col-form-label">出勤・退勤</label>
                <div class="col-sm-5">
                    <input type="time" class="form-control @error('check_in') is-invalid @enderror" name="check_in"
                        value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}"
                        {{ $hasPendingCorrection ? 'disabled' : '' }}>
                    @error('check_in')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="col-sm-5">
                    <input type="time" class="form-control @error('check_out') is-invalid @enderror" name="check_out"
                        value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}"
                        {{ $hasPendingCorrection ? 'disabled' : '' }}>
                    @error('check_out')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- 休憩 -->
            <div class="form-group row mt-3">
                <label class="col-sm-2 col-form-label">休憩</label>
                <div class="col-sm-10">
                    @foreach ($attendance->breaks as $index => $break)
                        <div class="row mb-2">
                            <!-- 休憩ID（hiddenフィールドで送信） -->
                            <input type="hidden" name="breaks[{{ $index }}][id]" value="{{ $break->id }}">
                            <!-- 休憩開始 -->
                            <div class="col-sm-5">
                                <input type="time"
                                    class="form-control @error("breaks.{$index}.start_time") is-invalid @enderror"
                                    name="breaks[{{ $index }}][start_time]"
                                    value="{{ old("breaks.{$index}.start_time", \Carbon\Carbon::parse($break->start_time)->format('H:i')) }}"
                                    {{ $hasPendingCorrection ? 'disabled' : '' }}>
                                @error("breaks.{$index}.start_time")
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                            <!-- 休憩終了 -->
                            <div class="col-sm-5">
                                <input type="time"
                                    class="form-control @error("breaks.{$index}.end_time") is-invalid @enderror"
                                    name="breaks[{{ $index }}][end_time]"
                                    value="{{ old("breaks.{$index}.end_time", \Carbon\Carbon::parse($break->end_time)->format('H:i')) }}"
                                    {{ $hasPendingCorrection ? 'disabled' : '' }}>
                                @error("breaks.{$index}.end_time")
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- 備考 -->
            <div class="form-group row mt-3">
                <label for="note" class="col-sm-2 col-form-label">備考</label>
                <div class="col-sm-10">
                    <textarea class="form-control @error('note') is-invalid @enderror" id="note" name="note" rows="3"
                        {{ $hasPendingCorrection ? 'disabled' : '' }}>{{ old('note', $attendance->note) }}</textarea>
                    @error('note')
                        <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- 修正ボタン -->
            @if (!$hasPendingCorrection)
                <div class="form-group row mt-5 mb-5">
                    <div class="col-sm-10 offset-sm-2">
                        <button type="submit" class="btn btn-primary">修正</button>
                    </div>
                </div>
            @endif

            @if ($hasPendingCorrection)
                <div class="form-group row mt-3 mb-5">
                    <p class="text-danger text-center">*承認待ちのため修正はできません。</p>
                </div>
            @endif
        </form>
    </div>
@endsection
