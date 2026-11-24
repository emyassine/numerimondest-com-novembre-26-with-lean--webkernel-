<?php declare(strict_types=1);

namespace App\Studio;

/** Server-owned catalogue data for the commissioned services prototype. */
final class MandateCatalog
{
    private const CATEGORIES = [
        'brand' => 'Brand identity & doctrine', 'digital' => 'Digital presence & search assets',
        'systems' => 'Business systems & software', 'intel' => 'Advisory & research',
        'recruit' => 'Talent & recruitment', 'sales' => 'Sales development',
    ];

    /** @return array{categories: array<string,string>, mandates: list<array<string,mixed>>, extensions: array<string,list<array<string,mixed>>>, playbooks: list<array<string,mixed>>, modules: list<array<string,mixed>>, revenue_force: array<string,mixed>} */
    public static function data(): array
    {
        static $cached = null;
        if (is_array($cached)) {
            return $cached;
        }

        $mandates = [
            ['brand-core','brand','project','Foundation','Core Identity Mandate',24000,'Visual foundations to launch or structure a serious business, without excess.','Logo system · typography · palette · production files · usage guide'],
            ['brand-system','brand','project','System','Brand System & Verbal Architecture',48000,'A complete identity, editorial voice and operating assets, ready for teams to apply.','Full identity · verbal territory · templates · usage kit'],
            ['brand-doctrine','brand','project','Institutional','Institutional Brand Doctrine',85000,'End-to-end brand governance, strategic doctrine and a documented system for groups and houses.','Doctrine · brand book · governance model · multi-entity rollout',true],
            ['dig-flagship','digital','project','Executive','Executive Flagship',45000,'A five-to-six-page institutional presence, delivered in three weeks, to establish credibility immediately.','5–6 pages · bespoke design · performance engineering · launch'],
            ['dig-cocon','digital','project','Flagship asset','Search-Engineered Commercial Asset',150000,'Bespoke architecture, dedicated backend, expert copy and a full semantic cluster (pillar page plus themed satellites), engineered to rank and generate proprietary traffic.','Semantic cluster · custom backend · expert content · internal and external linking · technical SEO',true],
            ['dig-multi','digital','project','Programme','Multi-Market Search Authority Programme',320000,'Coordinated search assets across countries, languages or verticals, with a semantic cluster per market and central editorial governance.','2+ clusters · multilingual · shared backend · 12-month editorial plan',true],
            ['sys-sprint','systems','project','Sprint','Operational Workflow Sprint',35000,'Targeted automation of three to five critical workflows on the tools you already use.','Process mapping · 3–5 automations · documentation · handover'],
            ['sys-crm','systems','project','Infrastructure','CRM & Client Portal Infrastructure',65000,'A bespoke sales pipeline and client portal, connected to your existing tools.','CRM · client portal · roles and access · integrations'],
            ['sys-saas','systems','project','Platform','Proprietary SaaS & Platform Engineering',280000,'Design, backend architecture (Rust, cloud) and deployment of complete software products.','Architecture · Rust backend · front end · cloud infrastructure · deployment',true],
            ['sys-ent','systems','project','Enterprise','Enterprise Data & Platform Programme',520000,'An organisation-scale business platform: data, integrations, security, governance and a dedicated delivery team.','Target architecture · platform · IS integrations · security · run',true],
            ['intel-diag','intel','oneoff','Diagnostic','Executive Operations Diagnostic',4900,'A 90-minute working session and a written action plan, credited against any subsequent mandate.','90-minute session · written action plan',false,'Fixed fee · due before the session'],
            ['intel-study','intel','oneoff','Study','Primary Market & Sector Intelligence Study',65000,'Field research, verified expert interviews and a benchmark ahead of a major investment.','Expert interviews · benchmark · decision report · debrief'],
            ['intel-dd','intel','oneoff','Audit','Technical & Vendor Due Diligence',14500,'An independent review before you sign an expensive quote with an agency or software vendor.','Quote review · technical risks · counter-estimate · recommendation'],
            ['rec-ops','recruit','recruit','Execution & admin','The Best Recruit · Execution & Admin',null,'Fast recruitment of qualified operational and support profiles, remote or on site.','Sourcing · assessments · interviews · supported probation',false,'Due on signature of the hire','46% of monthly salary'],
            ['rec-exec','recruit','recruit','Executive','The Best Recruit · Executive & Director Search',null,'Confidential executive search and in-depth assessment of senior leaders.','Market mapping · direct approach · assessed shortlist · onboarding',true,'Due on signature of the hire','46% of monthly salary'],
            ['sales-desk','sales','project','Programme','Revenue Desk · 90-Day Programme',59000,'A dedicated outbound desk that tests your market, builds the sequences and delivers a documented pipeline.','Targeting · sequences · appointment setting · weekly reporting · final report'],
        ];
        $items = [];
        foreach ($mandates as $m) {
            [$id,$cat,$fmt,$tier,$name,$price,$scope,$deliverables] = $m;
            $terms = $m[9] ?? 'Fixed fee · 50% at kickoff, 50% on delivery';
            $priceText = $m[10] ?? null;
            $items[] = ['id'=>$id,'slug'=>$id,'category'=>self::CATEGORIES[$cat],'cat'=>$cat,'format'=>match($fmt){'oneoff'=>'Diagnostic / study','recruit'=>'Mandate',default=>'Project'},'fmt'=>$fmt,'tier'=>$tier,'name'=>$name,'scope'=>$scope,'deliverables'=>$deliverables,'price_mad'=>$price,'price_amount'=>$price,'price_currency'=>'MAD','price_text'=>$priceText,'price_display'=>$priceText ?? ($price === null ? '' : 'From '.price((float)$price,'MAD',null,'prestige')),'terms'=>$terms,'flagship'=>(bool)($m[8] ?? false),'extensions'=>[],'type'=>'mandate','href'=>simple_site('studio','services/'.$id)];
        }
        $ext = [
            'brand-system'=>[['x-brand-kit','Social and motion template kit',12000]],
            'brand-doctrine'=>[['x-brand-multi','Multi-entity brand architecture',30000]],
            'dig-flagship'=>[['x-flag-bi','Arabic and English bilingual build',12000],['x-flag-lead','Booking and lead-capture integration',9000]],
            'dig-cocon'=>[['x-cocon-10','10-page expert cluster',25000,'A new themed cluster to capture an additional vertical.'],['x-cocon-ml','Multilingual pack',40000,'Cluster localised into a second language.'],['x-cocon-links','Authority link-building programme',35000]],
            'dig-multi'=>[['x-multi-mkt','Additional market cluster',90000],['x-multi-gov','12-month editorial governance',60000]],
            'sys-sprint'=>[['x-sprint-flow','Additional workflow automation',9000,'Per workflow.'],['x-sprint-train','Team onboarding and training',6000]],
            'sys-crm'=>[['x-crm-int','Additional system integration',12000],['x-crm-app','White-label client mobile app',55000]],
            'sys-saas'=>[['x-saas-admin','Admin and analytics console',45000],['x-saas-pay','Payments and subscription billing',30000],['x-saas-sec','Security and penetration review',25000]],
            'sys-ent'=>[['x-ent-team','Dedicated delivery team, per quarter',150000]],
            'intel-study'=>[['x-study-geo','Additional sector or geography',30000]],
            'sales-desk'=>[['x-desk-seg','Additional target segment',20000],['x-desk-mo','Continue as a monthly desk',19900,'Billed monthly after the programme.',true]],
        ];
        $extensions = [];
        foreach ($ext as $parent => $rows) foreach ($rows as $row) {
            [$id,$name,$amount] = $row; $monthly = (bool)($row[4] ?? false);
            $extensions[$parent][] = ['id'=>$id,'slug'=>$id,'name'=>$name,'description'=>$row[3] ?? null,'parent'=>$parent,'parent_name'=>array_column($items,'name','id')[$parent],'price_mad'=>$amount,'price_amount'=>$amount,'price_currency'=>'MAD','price_interval'=>$monthly?'/ month':null,'billing'=>$monthly?'monthly':'one_time','monthly'=>$monthly,'visibility'=>'detail-only','type'=>'extension','category'=>'Extension · '.array_column($items,'name','id')[$parent],'format'=>'Extension','price_display'=>price((float)$amount,'MAD',null,'prestige'),'href'=>simple_site('studio','services/'.$parent)];
        }
        foreach ($items as &$item) {
            $item['extensions'] = $extensions[$item['id']] ?? [];
            $item['engagement_type'] = $item['format'];
            $item['card_description'] = $item['scope'];
            $item['status'] = 'Taking enquiries';
            $item['price_lead'] = 'From ';
        }
        unset($item);
        $playbooks = [
            ['pb-sem','Semantic Authority Blueprint',19000,'SMEs and e-commerce brands that want to win on Google without paying for ads indefinitely.','Cluster map (1 pillar, 15–25 satellites) · internal linking plan · writing briefs · technical audit','Search-Engineered Commercial Asset'],
            ['pb-rem','Remote Operations & Hiring Blueprint',15000,'Leaders who want to hire and manage remotely without chaos.','Candidate scorecards · interview grids · onboarding SOPs · weekly operating rhythm','The Best Recruit'],
            ['pb-out','Outbound & Pipeline Blueprint',16500,'B2B firms and practices that sign only through word of mouth.','ICP definition · outreach sequences · qualification scripts · CRM structure','Revenue Desk · 90-Day Programme'],
            ['pb-saas','Software & SaaS Scoping Blueprint',29000,'Founders and large accounts before committing 300,000 MAD or more to development.','Functional architecture · stack choice (Rust, cloud) · data model · costing · roadmap','Proprietary SaaS & Platform Engineering'],
            ['pb-brand','Brand Positioning & Doctrine Blueprint',18000,'Companies whose image no longer reflects the real level of their service.','Brand platform · competitive positioning · offer architecture · editorial line','Institutional Brand Doctrine'],
        ];
        $playbooks = array_map(static fn($p)=>['id'=>$p[0],'slug'=>$p[0],'name'=>$p[1],'price_mad'=>$p[2],'price_amount'=>$p[2],'price_currency'=>'MAD','price_display'=>price((float)$p[2],'MAD',null,'prestige'),'target'=>$p[3],'deliverables'=>$p[4],'gateway'=>$p[5],'category'=>'Executive Playbook','format'=>'Playbook','type'=>'playbook','credit_window_days'=>30,'credit_percent'=>100,'href'=>simple_site('studio','services')],$playbooks);
        $modules = [
            ['mod-auto','Driving Schools Module',690,'Candidate records, driving-lesson scheduling, exam tracking, payments and automated reminders.'],
            ['mod-school','Private Schools & Training Centres Module',990,'Enrolment, monthly invoicing, absence tracking, parent portal and communication.'],
            ['mod-book','Bookshops & Trade Module',790,'Multi-reference stock, fast checkout, supplier orders and a synchronised online catalogue.'],
        ];
        $modules = array_map(static fn($m)=>['id'=>$m[0],'slug'=>$m[0],'name'=>$m[1],'price_mad'=>$m[2],'price_amount'=>$m[2],'price_currency'=>'MAD','price_interval'=>'/ month','price_display'=>'From '.price((float)$m[2],'MAD',null,'prestige').' / month','scope'=>$m[3],'category'=>'Vertical Software','format'=>'Subscription','type'=>'subscription','billing'=>'monthly','status'=>'waitlist_open','onboarding_min_mad'=>1500,'onboarding_max_mad'=>3000,'href'=>simple_site('studio','services#modules')],$modules);
        $revenueForce = ['id'=>'sales-force','slug'=>'sales-force','name'=>'Revenue Force · Recruit, Equip, Supervise','category'=>'Sales development','format'=>'Revenue Force','type'=>'revenue_force','price_mad'=>7500,'price_amount'=>null,'price_currency'=>'MAD','price_display'=>'Setup '.price(7500,'MAD',null,'prestige').' + weekly supervision '.price(4500,'MAD',null,'prestige').'–'.price(6500,'MAD',null,'prestige').' / month','monthly_min_mad'=>4500,'monthly_max_mad'=>6500,'href'=>simple_site('studio','services')];
        $cached = ['categories'=>self::CATEGORIES,'mandates'=>$items,'extensions'=>$extensions,'playbooks'=>$playbooks,'modules'=>$modules,'revenue_force'=>$revenueForce];
        return $cached;
    }

    /** @return array<string,array<string,mixed>> */
    public static function selectable(): array
    {
        $data = self::data(); $all = [];
        foreach ($data['mandates'] as $offer) $all[$offer['id']] = $offer;
        foreach ($data['extensions'] as $rows) foreach ($rows as $offer) $all[$offer['id']] = $offer;
        foreach ($data['playbooks'] as $offer) $all[$offer['id']] = $offer;
        foreach ($data['modules'] as $offer) $all[$offer['id']] = $offer;
        $all['sales-force'] = $data['revenue_force'];
        return $all;
    }
}
