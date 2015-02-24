

<h3>{{$header}}</h3>

<h4>{{$subheader}}</h4>

<div id="prvmail-wrapper" >
<form id="prvmail-form" action="wallmessage/{{$nickname}}" method="post" >

{{$parent}}

<div id="prvmail-to-label">{{$to}}</div>
{{$recipname}}

<div id="prvmail-subject-label">{{$subject}}</div>
<input type="text" size="64" maxlength="255" id="prvmail-subject" name="subject" value="{{$subjtxt|escape:'html'}}" {{$readonly}} tabindex="11" />

<div id="prvmail-message-label">{{$yourmessage}}</div>
<textarea rows="8" cols="72" class="prvmail-text" id="prvmail-text" name="body" tabindex="12">{{$text}}</textarea>


<div id="prvmail-submit-wrapper" >
	<input type="submit" id="prvmail-submit" name="submit" value="Submit" tabindex="13" />
	<div id="prvmail-link-wrapper" >
		<div id="prvmail-link" class="icon border link" title="{{$insert|escape:'html'}}" onclick="jotGetLink();" ></div>
	</div> 
	<div id="prvmail-rotator-wrapper" >
		<img id="prvmail-rotator" src="images/rotator.gif" alt="{{$wait|escape:'html'}}" title="{{$wait|escape:'html'}}" style="display: none;" />
	</div> 
</div>
<div id="prvmail-end"></div>
</form>
</div>
