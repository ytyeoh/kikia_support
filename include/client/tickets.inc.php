<?php
if(!defined('OSTCLIENTINC') || !is_object($thisclient) || !$thisclient->isValid()) die('Access Denied');
$settings = &$_SESSION['client:Q'];
// Unpack search, filter, and sort requests
if (isset($_REQUEST['clear']))
    $settings = array();
if (isset($_REQUEST['keywords'])) {
    $settings['keywords'] = $_REQUEST['keywords'];
}
if (isset($_REQUEST['topic_id'])) {
    $settings['topic_id'] = $_REQUEST['topic_id'];
}
if (isset($_REQUEST['status'])) {
    $settings['status'] = $_REQUEST['status'];
}

$org_tickets = $thisclient->canSeeOrgTickets();
if ($settings['keywords']) {
    // Don't show stat counts for searches
    $openTickets = $closedTickets = -1;
}
elseif ($settings['topic_id']) {
    $openTickets = $thisclient->getNumTopicTicketsInState($settings['topic_id'],
        'open', $org_tickets);
    $closedTickets = $thisclient->getNumTopicTicketsInState($settings['topic_id'],
        'closed', $org_tickets);
}
else {
    $openTickets = $thisclient->getNumOpenTickets($org_tickets);
    $closedTickets = $thisclient->getNumClosedTickets($org_tickets);
}


$tickets = TicketModel::objects();

$qs = array();
$status=null;

if ($settings['status'])
    $status = strtolower($settings['status']);
    switch ($status) {
    default:
        $status = 'open';
    case 'open':
    case 'closed':
		$results_type = ($status == 'closed') ? __('Closed Tickets') : __('Open Tickets');
        $tickets->filter(array('status__state' => $status));
        break;
}

$sortOptions=array('id'=>'number', 'subject'=>'cdata__subject',
                    'status'=>'status__name', 'dept'=>'dept__name','date'=>'created');
$orderWays=array('DESC'=>'-','ASC'=>'');
//Sorting options...
$order_by=$order=null;
$sort=($_REQUEST['sort'] && $sortOptions[strtolower($_REQUEST['sort'])])?strtolower($_REQUEST['sort']):'date';
if($sort && $sortOptions[$sort])
    $order_by =$sortOptions[$sort];

$order_by=$order_by ?: $sortOptions['date'];
if($_REQUEST['order'] && $orderWays[strtoupper($_REQUEST['order'])])
    $order=$orderWays[strtoupper($_REQUEST['order'])];

$x=$sort.'_sort';
$$x=' class="'.strtolower($_REQUEST['order'] ?: 'desc').'" ';

// Add visibility constraints
$tickets->filter(Q::any(array(
    'user_id' => $thisclient->getId(),
    'thread__collaborators__user_id' => $thisclient->getId(),
)));

// Perform basic search
if ($settings['keywords']) {
    $q = $settings['keywords'];
    if (is_numeric($q)) {
        $tickets->filter(array('number__startswith'=>$q));
    } else { //Deep search!
        // Use the search engine to perform the search
        $tickets = $ost->searcher->find($q, $tickets);
    }
}

if ($settings['topic_id']) {
    $tickets = $tickets->filter(array('topic_id' => $settings['topic_id']));
}
TicketForm::ensureDynamicDataView();

$total=$tickets->count();
$page=($_GET['p'] && is_numeric($_GET['p']))?$_GET['p']:1;
$pageNav=new Pagenate($total, $page, PAGE_LIMIT);
$qstr = '&amp;'. Http::build_query($qs);
$qs += array('sort' => $_REQUEST['sort'], 'order' => $_REQUEST['order']);
$pageNav->setURL('tickets.php', $qs);
$pageNav->paginate($tickets);

$showing =$total ? $pageNav->showing() : "";
if(!$results_type)
{
	$results_type=ucfirst($status).' '.__('Tickets');
}
$showing.=($status)?(' '.$results_type):' '.__('All Tickets');
if($search)
    $showing=__('Search Results').": $showing";

$negorder=$order=='-'?'ASC':'DESC'; //Negate the sorting

$tickets->order_by($order.$order_by);
$tickets->values(
    'ticket_id', 'number', 'created', 'isanswered', 'source', 'status_id',
    'status__state', 'status__name', 'cdata__subject', 'dept_id',
    'dept__name', 'dept__ispublic', 'user__default_email__address'
);
?>

<div class="row ">
	<div class="col-md-12" >
	<form action="tickets.php" method="get" id="ticketSearchForm">
		<div class="col-md-6">
			<div class="input-group">   
			   <input type="hidden" name="a"  value="search">
				<input class="search form-control" type="search" name="keywords" size="30" value="<?php echo Format::htmlchars($settings['keywords']); ?>" placeholder="Search Tickets">
				 <span class="input-group-btn">
					<input class="btn btn-info" type="submit" value="<?php echo __('Search');?>">
				 </span>
			</div>
			<div>
			<?php if ($settings['keywords'] || $settings['topic_id'] || $_REQUEST['sort']) { ?>
			<div style="margin-top:10px"><strong><a href="?clear" style="color:#777"><i class="icon-remove-circle"></i> <?php echo __('Clear all filters and sort'); ?></a></strong></div>
			<?php } ?>
			</div>
		</div>
		<div class="col-md-2 text-right">
			<h4 style="color: #337ab7;"><?php echo __('Help Topic'); ?>:</h4>
		</div>
		<div class="col-md-4">
			<select name="topic_id" class="nowarn form-control" " onchange="javascript: this.form.submit(); ">
				<option value="">&mdash; <?php echo __('All Help Topics');?> &mdash;</option>
		<?php foreach (Topic::getHelpTopics(true) as $id=>$name) {
				$count = $thisclient->getNumTopicTickets($id);
				if ($count == 0)
					continue;
		?>
				<option value="<?php echo $id; ?>"i
					<?php if ($settings['topic_id'] == $id) echo 'selected="selected"'; ?>
					><?php echo sprintf('%s (%d)', Format::htmlchars($name),
						$thisclient->getNumTopicTickets($id)); ?></option>
		<?php } ?>
			</select>
		</div>

	</form>
	</div>
</div>

<div class="clearfix"></div>

<div class="row">

		<div class="col-xs-6 col-md-6">
			<h2>
				<a href="<?php echo Format::htmlchars($_SERVER['REQUEST_URI']); ?>">
				<i class="refresh icon-refresh"></i>
				<?php echo __('Tickets'); ?>
				</a>
			</h2>
		</div>
		<div class="col-xs-6 col-md-6 text-right">
			<h3 style="color: #337ab7;">
			
			<a class="action-button btn-lg <?php if ($status == 'open') echo 'active'; ?>"
				href="?<?php echo Http::build_query(array('a' => 'search', 'status' => 'open')); ?>">
			<i class="icon-file-alt"></i> <?php echo sprintf('%s (%d)', _P('ticket-status', 'Open'), $thisclient->getNumOpenTickets()); ?>
			</a>
			
	
			<span style="color:lightgray">|</span>
		
			
			<a class="action-button btn-lg <?php if ($status == 'closed') echo 'active'; ?>"
				href="?<?php echo Http::build_query(array('a' => 'search', 'status' => 'closed')); ?>">
			<i class="icon-file-text"></i> <?php echo sprintf('%s (%d)', __('Closed'), $thisclient->getNumClosedTickets()); ?>
			</a>
			</h3>
		</div>

</div>
<div class="row">
<div id="no-more-tables">
<table id="ticketTable" width="800" border="0" cellspacing="0" cellpadding="0" class="table table-striped table-hover table-condensed cf">
    <div><?php echo $showing; ?></div>
    <thead class="cf">
        <tr>
            <th class="text-nowrap">
                <a href="tickets.php?sort=ID&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Ticket ID"><?php echo __('Ticket #');?></a>
            </th>
            <th class="text-nowrap">
                <a href="tickets.php?sort=date&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Date"><?php echo __('Create Date');?></a>
            </th>
            <th class="text-nowrap">
                <a href="tickets.php?sort=status&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Status"><?php echo __('Status');?></a>
            </th>
            <th >
                <a href="tickets.php?sort=subj&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Subject"><?php echo __('Subject');?></a>
            </th>
            <th class="hidden-xs">
                <a href="tickets.php?sort=dept&order=<?php echo $negorder; ?><?php echo $qstr; ?>" title="Sort By Department"><?php echo __('Department');?></a>
            </th>
        </tr>
    </thead>
    <tbody>
    <?php
     $subject_field = TicketForm::objects()->one()->getField('subject');
     $defaultDept=Dept::getDefaultDeptName(); //Default public dept.
     if ($tickets->exists(true)) {
         foreach ($tickets as $T) {
            $dept = $T['dept__ispublic']
                ? Dept::getLocalById($T['dept_id'], 'name', $T['dept__name'])
                : $defaultDept;
            $subject = $subject_field->display(
                $subject_field->to_php($T['cdata__subject']) ?: $T['cdata__subject']
            );
            $status = TicketStatus::getLocalById($T['status_id'], 'value', $T['status__name']);
            if (false) // XXX: Reimplement attachment count support
                $subject.='  &nbsp;&nbsp;<span class="Icon file"></span>';
            $ticketNumber=$T['number'];
            if($T['isanswered'] && !strcasecmp($T['status__state'], 'open')) {
                $subject="<b>$subject</b>";
                $ticketNumber="<b>$ticketNumber</b>";
            }
            ?>
            <tr id="<?php echo $T['ticket_id']; ?>">
                <td data-title="Ticket #"  class="text-nowrap">
                <a class="Icon <?php echo strtolower($T['source']); ?>Ticket" title="<?php echo $T['user__default_email__address']; ?>"
                    href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo $ticketNumber; ?></a>
                </td>
                <td data-title="Created"  class="text-nowrap">&nbsp;<?php echo Format::date($T['created']); ?></td>
                <td data-title="Status"  class="text-nowrap">&nbsp;<?php echo $status; ?></td>
                <td data-title="Subject" >
                    <div style="max-height: 1.2em; max-width: 320px;" class="link truncate" href="tickets.php?id=<?php echo $T['ticket_id']; ?>"><?php echo mb_strimwidth($subject, 0, 40, "..."); ?></div>
                </td>
                <td  class="hidden-xs">&nbsp;<span class="truncate"><?php echo $dept; ?></span></td>
            </tr>
        <?php
        }
     } else {
         echo '<tr><td colspan="5">'.__('Your query did not match any records').'</td></tr>';
     }
    ?>
    </tbody>
</table>
</div>
<?php
if ($total) {
    echo '<div>&nbsp;'.__('Page').':'.$pageNav->getPageLinks().'&nbsp;</div>';
}
?>
</div>
