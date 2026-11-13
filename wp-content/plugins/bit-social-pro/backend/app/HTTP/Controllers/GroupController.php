<?php

namespace BitApps\SocialPro\HTTP\Controllers;

use BitApps\Social\Config as FreeConfig;
use BitApps\Social\Model\Account;
use BitApps\SocialPro\Deps\BitApps\WPKit\Helpers\Arr;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Request\Request;
use BitApps\SocialPro\Deps\BitApps\WPKit\Http\Response;
use BitApps\SocialPro\Model\Group;
use BitApps\SocialPro\Model\GroupsAccount;

class GroupController
{
    public function index()
    {
        global $wpdb;
        $accountTable = FreeConfig::withDBPrefix('accounts');
        $groupsAccountsTable = FreeConfig::withDBPrefix('groups_accounts');

        $arr = new Arr();

        $groups = Group::get(['id', 'name']);
        $groupIdsArray = $arr->pluck($groups, 'id');
        $groupIds = implode(',', $groupIdsArray);

        $sql = " SELECT
                {$accountTable}.id,
                {$accountTable}.details,
                {$accountTable}.platform,
            {$groupsAccountsTable}.group_id AS bs_group_id,
            {$groupsAccountsTable}.account_id AS bs_account_id
            FROM
            {$accountTable}
            INNER JOIN {$groupsAccountsTable} ON {$accountTable}.id = {$groupsAccountsTable}.account_id
            WHERE
            {$groupsAccountsTable}.group_id IN ({$groupIds})";

        $accountsByGroup = $wpdb->get_results($sql);

        foreach ($accountsByGroup as $account) {
            $account->details = json_decode($account->details);
        }

        foreach ($groups as $group) {
            $groupId = $group['id'];

            $filteredAccounts = array_filter($accountsByGroup, function ($account) use ($groupId) {
                return $account->bs_group_id == $groupId;
            });

            $group['accounts'] = array_values($filteredAccounts);
        }

        return Response::SUCCESS($groups);
    }

    public function store(Request $request)
    {
        $validatedData = (object) $request->validate([
            'name' => ['required', 'string'],
        ]);
        $duplicate = Group::where('name', $validatedData->name)->count();
        if ($duplicate) {
            return Response::error("This {$validatedData->name} already exist");
        }

        $groupData = ['name' => $validatedData->name];

        $response = Group::insert($groupData);

        return Response::success($response);
    }

    public function update(Request $request, $groupId)
    {
        $validatedData = (object) $request->validate([
            'name'       => ['nullable', 'string'],
            'accountIds' => ['nullable', 'array'],
        ]);

        $group = Group::findOne(['id' => $groupId]);

        if (!$group) {
            return Response::error('Group not found');
        }

        if (isset($validatedData->name)) {
            $duplicate = Group::findOne(['name' => $validatedData->name]);

            if ($duplicate) {
                return Response::error($duplicate->id === (int) $groupId
                    ? "This {$validatedData->name} already the same"
                    : "This {$validatedData->name} already exists");
            }

            $group->name = $validatedData->name;
            $group->save();

            return Response::success('Name update successfully');
        }

        if (isset($validatedData->accountIds)) {
            $accountIds = $validatedData->accountIds;
            // Remove existing group accounts
            GroupsAccount::where('group_id', $groupId)->delete();

            // Insert updated group account data
            $groupAccountData = [];

            foreach ($accountIds as $accountId) {
                $groupAccountData[] = ['group_id' => $groupId, 'account_id' => $accountId];
            }

            if (\count($groupAccountData)) {
                GroupsAccount::insert($groupAccountData);
            }

            return Response::success($group);
        }
    }

    public function destroy(Group $group)
    {
        $group->delete();

        return Response::success('Group deleted successfully');
    }

    public function getGroupAccounts(Group $group)
    {
        $groupAccount = $group['accountIds'];
        $arr = new Arr();
        $accountIds = $arr->pluck($groupAccount, 'account_id');

        return Response::success($accountIds);
    }

    public function removeAccountFromGroup($groupId, $accountId)
    {
        $groupAccountModel = GroupsAccount::where('group_id', $groupId)->where('account_id', $accountId);

        $groupAccountModel->delete();

        return Response::success('Account remove from group successfully');
    }
}
