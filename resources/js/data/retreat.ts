export type RetreatRoute = {
    id: number;
    title: string;
    location: string;
    region: string;
    days: number;
    people: string;
    summary: string;
    tags: string[];
    palette: string;
    accent: string;
    updatedAt: string;
    favorite: boolean;
    cover?: string;
    importedByUnion?: boolean;
    rating?: number | null;
    reviewCount?: number;
    highlights?: string[];
    funding?: {
        dailySubsidy: number;
        estimatedSubsidy: number;
        selfFundedItems: string[];
        disclaimer: string;
    };
    service?: {
        inboundTransport: string;
        outboundTransport: string;
        hotels: string;
        meals: string;
        localTransport: string;
        tickets: string;
        guide: string;
        insurance: string;
        extras: string[];
        notices: string[];
    };
};

export type RetreatGroup = {
    id: number;
    title: string;
    route: string;
    leader: string;
    leaderId?: number;
    isLeader?: boolean;
    membershipStatus?: 'pending' | 'approved' | 'rejected' | null;
    department: string;
    date: string;
    deadline: string;
    joined: number;
    capacity: number;
    min: number;
    status:
        | '报名中'
        | '即将满员'
        | '已成团'
        | '已截止'
        | '已结束'
        | '未成团'
        | '已取消';
    palette: string;
    location?: string;
    region?: string;
    days?: number;
    departureDate?: string;
    returnDate?: string;
    applicationDeadline?: string;
    meetingInfo?: string | null;
    notes?: string | null;
    attachmentName?: string | null;
    attachmentUrl?: string | null;
    wechatQrCodeName?: string | null;
    wechatQrCodeUrl?: string | null;
    canReview?: boolean;
    reviewed?: boolean;
    contactMobile?: string | null;
    finalConfirmationStatus?:
        'not_required' | 'pending' | 'confirmed' | 'declined';
    rawStatus?: 'open' | 'formed' | 'failed' | 'cancelled';
    statusReason?: string | null;
    finalConfirmationDeadline?: string | null;
    confirmationCounts?: {
        pending: number;
        confirmed: number;
        declined: number;
    } | null;
    approvalMode?: 'automatic' | 'manual';
};

export type ItineraryDay = {
    day: number;
    title: string;
    location: string;
    period: string;
    plan: string;
    stay: string;
    note?: string;
};

export const retreatRoutes: RetreatRoute[] = [
    {
        id: 7,
        title: '北国风光 · 长春长白山延吉五日',
        location: '吉林 · 长春 / 长白山 / 延吉',
        region: '东北',
        days: 5,
        people: '20—25 人',
        summary:
            '从长春城市记忆出发，深入长白山火山地貌与森林生态，再到延吉体验朝鲜族文化与城市烟火，兼顾自然疗愈、人文走读和特色餐饮。',
        tags: ['长白山', '朝鲜族文化', '森林康养'],
        palette: 'from-[#173f4a] via-[#32667a] to-[#91ad9d]',
        accent: '#f2d49b',
        updatedAt: '09-18',
        favorite: false,
        cover: '/images/retreat/changchun-changbaishan-yanji-cover.png',
        highlights: [
            '长白山北景区及天池火山地貌',
            '长春电影文化与历史教育',
            '延边朝鲜族民俗与特色餐饮',
            '森林漂流与二道白河慢游体验',
        ],
        funding: {
            dailySubsidy: 500,
            estimatedSubsidy: 2500,
            selfFundedItems: [
                '杭州往返长春机票及机场往返交通',
                '个人消费、行李超额费及方案未列明项目',
            ],
            disclaimer:
                '工会按每人每天 500 元标准提供疗休养经费，超出补助范围及明确列示的项目由个人自行承担。',
        },
        service: {
            inboundTransport: '杭州—长春 CZ6546，参考时刻 11:50—15:00',
            outboundTransport: '长春—杭州 CZ6405，参考时刻 16:00—19:05',
            hotels: '全程网评四钻标准酒店，2 人 1 间；长春、二道白河、延吉各安排同等级酒店。',
            meals: '含 4 次酒店早餐、9 次正餐；结合东北铁锅炖、延吉烤肉等特色餐。',
            localTransport: '全程空调旅游车，根据团队人数安排合适车型。',
            tickets: '包含行程所列景点首道门票及长白山环线车、环保车。',
            guide: '全程专业中文导游服务，关键景点提供讲解。',
            insurance: '旅行社责任险及旅游意外保险，具体保额以最终保单为准。',
            extras: [
                '提供旅行包、旅行帽、雨具、饮用水及常备外用药品',
                '疗休养期间生日成员可安排生日关怀',
                '全程不安排购物点，不推荐强制自费项目',
            ],
            notices: [
                '请携带有效身份证件，并提前核对航班或车次信息。',
                '景区安排可能因天气、交通及客流变化调整，以安全为先。',
                '建议穿防滑平底鞋，根据长白山气候准备保暖及防雨衣物。',
                '请如实告知影响出行安全的健康情况，并自备常用药品。',
            ],
        },
    },
    {
        id: 1,
        title: '松风入海 · 舟山慢岛五日',
        location: '浙江 · 舟山',
        region: '华东',
        days: 5,
        people: '12—24 人',
        summary:
            '沿海岛步道与渔村文化相遇，在海风、松林和慢节奏中完成一次温和的身心复原。',
        tags: ['滨海', '轻徒步', '人文'],
        palette: 'from-[#214f48] via-[#376b60] to-[#90a992]',
        accent: '#e7c591',
        updatedAt: '09-12',
        favorite: true,
    },
    {
        id: 2,
        title: '云上梯田 · 黔东南六日',
        location: '贵州 · 黔东南',
        region: '西南',
        days: 6,
        people: '15—30 人',
        summary:
            '走进秋日梯田与苗寨，串联非遗体验、森林漫行和村落共餐，感受山地生活的秩序。',
        tags: ['非遗', '村落', '摄影'],
        palette: 'from-[#796142] via-[#9b875d] to-[#c1b48d]',
        accent: '#f2dcc1',
        updatedAt: '09-09',
        favorite: false,
    },
    {
        id: 3,
        title: '竹径茶山 · 安吉静养四日',
        location: '浙江 · 安吉',
        region: '华东',
        days: 4,
        people: '10—20 人',
        summary:
            '以竹林晨行、茶园体验与自然课堂为主线，适合希望减少舟车劳顿的轻量疗休养团队。',
        tags: ['茶山', '静养', '短途'],
        palette: 'from-[#365c3b] via-[#65815b] to-[#adb991]',
        accent: '#e8d9a7',
        updatedAt: '09-06',
        favorite: false,
    },
    {
        id: 4,
        title: '古厝听潮 · 闽南文化五日',
        location: '福建 · 泉州',
        region: '华南',
        days: 5,
        people: '12—28 人',
        summary:
            '在古城巷陌、海丝遗迹和惠安海岸之间行走，兼顾文化深度与舒缓的旅行节奏。',
        tags: ['古城', '海丝', '美食'],
        palette: 'from-[#6b4032] via-[#a4654d] to-[#d2ad85]',
        accent: '#f4dfba',
        updatedAt: '08-28',
        favorite: true,
    },
    {
        id: 5,
        title: '林海温泉 · 长白山六日',
        location: '吉林 · 长白山',
        region: '东北',
        days: 6,
        people: '16—30 人',
        summary:
            '穿行秋季林海，安排温泉恢复与自然观察，适合希望拥有完整休整周期的教职工团队。',
        tags: ['森林', '温泉', '自然'],
        palette: 'from-[#2f4c4e] via-[#577072] to-[#a4b2ad]',
        accent: '#e9d6ad',
        updatedAt: '08-21',
        favorite: false,
    },
    {
        id: 6,
        title: '湖山书院 · 徽州研游五日',
        location: '安徽 · 黄山',
        region: '华东',
        days: 5,
        people: '12—24 人',
        summary:
            '从徽州古村到山水书院，以文化走读和低强度漫游为主，保留充足的自由休息时间。',
        tags: ['书院', '古村', '走读'],
        palette: 'from-[#394b45] via-[#66736b] to-[#b2ad98]',
        accent: '#ead4a5',
        updatedAt: '08-17',
        favorite: false,
    },
];

export const retreatGroups: RetreatGroup[] = [
    {
        id: 1,
        title: '十月舟山慢岛疗休养团',
        route: '松风入海 · 舟山慢岛五日',
        leader: '周岚',
        department: '人文学院',
        date: '10月18日—10月22日',
        deadline: '还剩 6 天',
        joined: 17,
        capacity: 24,
        min: 12,
        status: '报名中',
        palette: 'from-[#1c514b] to-[#789987]',
    },
    {
        id: 2,
        title: '安吉竹海静养团',
        route: '竹径茶山 · 安吉静养四日',
        leader: '钱弘毅',
        department: '信息工程学院',
        date: '10月25日—10月28日',
        deadline: '还剩 3 天',
        joined: 18,
        capacity: 20,
        min: 10,
        status: '即将满员',
        palette: 'from-[#47603d] to-[#a3b083]',
    },
    {
        id: 3,
        title: '黔东南梯田摄影团',
        route: '云上梯田 · 黔东南六日',
        leader: '沈嘉禾',
        department: '艺术学院',
        date: '11月02日—11月07日',
        deadline: '还剩 12 天',
        joined: 21,
        capacity: 30,
        min: 15,
        status: '报名中',
        palette: 'from-[#735b3e] to-[#b6a578]',
    },
    {
        id: 4,
        title: '徽州湖山书院走读团',
        route: '湖山书院 · 徽州研游五日',
        leader: '陆清远',
        department: '马克思主义学院',
        date: '10月12日—10月16日',
        deadline: '已截止',
        joined: 22,
        capacity: 24,
        min: 12,
        status: '已成团',
        palette: 'from-[#40544d] to-[#9c9d86]',
    },
];

export const routeItineraries: Record<number, ItineraryDay[]> = {
    7: [
        {
            day: 1,
            title: '飞抵长春 · 城市电影记忆',
            location: '杭州—长春',
            period: '上午集合 / 下午游览 / 晚间休息',
            plan: '参考航班 CZ6546 前往长春。抵达后游览长春电影制片厂旧厂区，了解中国电影发展脉络；随后前往“这有山”文旅街区自由体验。',
            stay: '长春网评四钻标准酒店',
            note: '含中餐、晚餐；具体航班以出票为准。',
        },
        {
            day: 2,
            title: '林海穿行 · 二道白河体验',
            location: '长春—二道白河',
            period: '上午乘车 / 下午体验 / 晚间特色活动',
            plan: '乘车约 4.5 小时前往二道白河，下午安排森林漂流与恩都里文化街区；晚间品尝东北铁锅炖并体验地方文化活动。',
            stay: '二道白河网评四钻标准酒店',
            note: '含早、中、晚餐；漂流项目根据天气和水情调整。',
        },
        {
            day: 3,
            title: '长白山天池 · 延吉夜色',
            location: '长白山北景区—延吉',
            period: '全天游览 / 傍晚抵达延吉',
            plan: '游览长白山北景区，包含天池、长白瀑布、小天池、绿渊潭和聚龙温泉群等；随后前往延吉，在延边大学网红墙周边自由体验。',
            stay: '延吉网评四钻标准酒店',
            note: '含早、中、晚餐；天池开放情况受天气影响。',
        },
        {
            day: 4,
            title: '朝鲜族民俗 · 返回长春',
            location: '延吉—长春',
            period: '上午民俗体验 / 下午返程',
            plan: '早起可自由前往水上市场，随后参观延边朝鲜族民俗园；午餐后乘车约 4.5 小时返回长春。',
            stay: '长春网评四钻标准酒店',
            note: '含早、中、晚餐；民俗园体验项目以现场安排为准。',
        },
        {
            day: 5,
            title: '历史教育 · 从容返杭',
            location: '长春—杭州',
            period: '上午参观 / 下午返程',
            plan: '参观伪满皇宫博物院，了解东北近现代历史；午餐后根据航班时间前往机场，参考航班 CZ6405 返回杭州。',
            stay: '—',
            note: '含早餐、中餐；返程交通以最终出票为准。',
        },
    ],
    1: [
        {
            day: 1,
            title: '抵达舟山 · 海岛初见',
            location: '定海古城',
            period: '上午出发 / 下午抵达',
            plan: '学校集合乘车前往舟山，抵达后办理入住；傍晚漫步定海古城，安排团员见面与行程说明。',
            stay: '定海区舒适型酒店',
            note: '首日以适应节奏为主，不安排高强度活动。',
        },
        {
            day: 2,
            title: '东海云廊 · 松林慢行',
            location: '东海云廊',
            period: '全天',
            plan: '沿山海步道开展低强度慢行，途中设置观景与休息节点；下午安排海洋文化分享。',
            stay: '定海区舒适型酒店',
        },
        {
            day: 3,
            title: '朱家尖 · 海风与沙岸',
            location: '朱家尖',
            period: '上午至傍晚',
            plan: '前往朱家尖南沙，开展沙岸漫步和自由活动；午后参观海洋主题场馆，傍晚观海。',
            stay: '朱家尖度假酒店',
            note: '根据天气调整滨海活动时间。',
        },
        {
            day: 4,
            title: '渔村日常 · 非遗体验',
            location: '东沙古镇',
            period: '全天',
            plan: '走访东沙古镇，体验渔绳结、鱼拓等传统技艺；下午保留两小时自由休整。',
            stay: '朱家尖度假酒店',
        },
        {
            day: 5,
            title: '晨间舒展 · 从容返程',
            location: '朱家尖—学校',
            period: '上午',
            plan: '早餐后进行轻量舒展与活动回顾，整理行李后统一乘车返校。',
            stay: '—',
        },
    ],
    2: [
        {
            day: 1,
            title: '抵达凯里 · 苗侗初见',
            location: '凯里',
            period: '下午',
            plan: '抵达凯里后入住，召开行前说明会，晚间品尝当地风味。',
            stay: '凯里酒店',
        },
        {
            day: 2,
            title: '西江苗寨 · 村落走读',
            location: '西江千户苗寨',
            period: '全天',
            plan: '村落建筑与生活方式走读，安排非遗银饰或蜡染体验。',
            stay: '西江特色客栈',
        },
        {
            day: 3,
            title: '加榜梯田 · 山地漫行',
            location: '从江加榜',
            period: '全天',
            plan: '沿梯田观景步道低强度行走，设置摄影观察与休息时段。',
            stay: '从江酒店',
        },
        {
            day: 4,
            title: '岜沙苗寨 · 文化交流',
            location: '岜沙',
            period: '上午至下午',
            plan: '了解苗族村落文化，下午返回酒店自由休整。',
            stay: '从江酒店',
        },
        {
            day: 5,
            title: '肇兴侗寨 · 鼓楼回响',
            location: '肇兴侗寨',
            period: '全天',
            plan: '鼓楼、风雨桥走读，与当地文化工作者开展交流。',
            stay: '肇兴特色客栈',
        },
        {
            day: 6,
            title: '堂安晨行 · 返程',
            location: '堂安—学校',
            period: '上午',
            plan: '梯田晨行及活动总结，午后统一返程。',
            stay: '—',
        },
    ],
    3: [
        {
            day: 1,
            title: '抵达安吉 · 竹乡初见',
            location: '安吉县',
            period: '下午',
            plan: '统一乘车抵达，入住后开展竹林周边适应性散步。',
            stay: '竹林度假酒店',
        },
        {
            day: 2,
            title: '竹海晨行 · 自然课堂',
            location: '中国大竹海',
            period: '全天',
            plan: '竹林晨行、自然观察与呼吸练习，下午安排自由休息。',
            stay: '竹林度假酒店',
        },
        {
            day: 3,
            title: '茶园慢作 · 手工体验',
            location: '溪龙乡',
            period: '全天',
            plan: '茶园参访、制茶体验与乡村午餐，控制步行强度。',
            stay: '竹林度假酒店',
        },
        {
            day: 4,
            title: '湖畔舒展 · 返程',
            location: '灵峰—学校',
            period: '上午',
            plan: '湖畔舒展和团队回顾，午餐后统一返校。',
            stay: '—',
        },
    ],
    4: [
        {
            day: 1,
            title: '抵达泉州 · 古城入巷',
            location: '泉州古城',
            period: '下午',
            plan: '入住后沿中山路慢行，了解古城空间与海丝文化。',
            stay: '泉州古城酒店',
        },
        {
            day: 2,
            title: '多元信仰 · 海丝走读',
            location: '开元寺—清净寺',
            period: '全天',
            plan: '以海丝文化为线索进行城市走读，途中安排充分休息。',
            stay: '泉州古城酒店',
        },
        {
            day: 3,
            title: '惠安海岸 · 古厝听潮',
            location: '崇武古城',
            period: '全天',
            plan: '参访崇武古城与海岸村落，下午进行非遗体验。',
            stay: '惠安海景酒店',
        },
        {
            day: 4,
            title: '蟳埔簪花 · 渔村日常',
            location: '蟳埔村',
            period: '上午至下午',
            plan: '了解渔村生活与女性习俗，保留自由活动时间。',
            stay: '泉州古城酒店',
        },
        {
            day: 5,
            title: '街巷余韵 · 返程',
            location: '泉州—学校',
            period: '上午',
            plan: '自由漫步与活动总结，午后返程。',
            stay: '—',
        },
    ],
    5: [
        {
            day: 1,
            title: '抵达延吉 · 林海序章',
            location: '延吉',
            period: '下午',
            plan: '抵达后入住并完成高海拔与天气安全说明。',
            stay: '延吉酒店',
        },
        {
            day: 2,
            title: '长白山北坡 · 火山地貌',
            location: '长白山北景区',
            period: '全天',
            plan: '分段游览天池、瀑布与温泉带，根据天气弹性调整。',
            stay: '二道白河酒店',
        },
        {
            day: 3,
            title: '原始森林 · 低强度漫行',
            location: '地下森林',
            period: '全天',
            plan: '森林步道自然观察，下午安排温泉恢复。',
            stay: '二道白河温泉酒店',
        },
        {
            day: 4,
            title: '湿地观鸟 · 自由休整',
            location: '碱水河湿地',
            period: '上午',
            plan: '湿地轻行与观鸟，下午整段自由休息。',
            stay: '二道白河温泉酒店',
        },
        {
            day: 5,
            title: '朝鲜族村落 · 文化体验',
            location: '延边民俗村',
            period: '全天',
            plan: '村落参访、饮食文化体验与团队交流。',
            stay: '延吉酒店',
        },
        {
            day: 6,
            title: '晨间回顾 · 返程',
            location: '延吉—学校',
            period: '上午',
            plan: '完成团队回顾后统一返程。',
            stay: '—',
        },
    ],
    6: [
        {
            day: 1,
            title: '抵达徽州 · 古村夜话',
            location: '宏村',
            period: '下午',
            plan: '入住后进行古村导览，晚间开展行程说明。',
            stay: '宏村精品民宿',
        },
        {
            day: 2,
            title: '南湖书院 · 徽派人文',
            location: '宏村—西递',
            period: '全天',
            plan: '走读南湖书院和徽派建筑，下午参访西递。',
            stay: '宏村精品民宿',
        },
        {
            day: 3,
            title: '黄山轻行 · 湖山相照',
            location: '黄山风景区',
            period: '全天',
            plan: '选择低强度游览段，缆车上下并设置多次休息。',
            stay: '汤口酒店',
        },
        {
            day: 4,
            title: '呈坎八卦 · 晒秋体验',
            location: '呈坎',
            period: '全天',
            plan: '村落文化走读与晒秋体验，下午自由休整。',
            stay: '徽州区酒店',
        },
        {
            day: 5,
            title: '文房雅集 · 从容返程',
            location: '屯溪—学校',
            period: '上午',
            plan: '文房体验及团队回顾，午后统一返程。',
            stay: '—',
        },
    ],
};

export function routeById(id: number): RetreatRoute {
    return retreatRoutes.find((route) => route.id === id) ?? retreatRoutes[0];
}

export function itineraryByRouteId(id: number): ItineraryDay[] {
    return routeItineraries[id] ?? routeItineraries[1];
}
